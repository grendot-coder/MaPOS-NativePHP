<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SaleService
{
    /**
     * Generate nomor transaksi penjualan kasir unik harian.
     * Format: TRX-YYYYMMDD-0001
     */
    public function generateTransactionNumber(): string
    {
        $todayPrefix = 'TRX-'.Carbon::now()->format('Ymd').'-';

        $query = Sale::withTrashed()
            ->where('no_transaksi', 'like', "{$todayPrefix}%")
            ->orderByRaw('LENGTH(no_transaksi) DESC, no_transaksi DESC');

        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $lastSale = $query->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->no_transaksi, strlen($todayPrefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $todayPrefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Simpan transaksi penjualan kasir dan potong stok secara atomik dalam satu transaksi basis data.
     *
     * @param  array<string, array{product_id: string, kode_produk: string, nama: string, harga_satuan: float, qty: int, subtotal: float}>  $cartItems
     */
    public function processSale(
        User $kasir,
        array $cartItems,
        float $total,
        float $bayar,
        float $kembali,
        ?string $cashShiftId = null,
        string $metodePembayaran = 'cash',
        ?string $customerId = null,
        int $poinDipakai = 0,
        float $potonganPoin = 0,
        array $dropshipData = []
    ): Sale {
        if (empty($cartItems)) {
            throw new InvalidArgumentException('Keranjang belanja kasir tidak boleh kosong.');
        }

        if (! in_array($metodePembayaran, ['cash', 'qris', 'transfer', 'tempo'], true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        if ($metodePembayaran === 'cash' && $bayar < $total) {
            throw new InvalidArgumentException('Uang bayar kurang dari total belanja.');
        }

        if ($metodePembayaran === 'tempo' && empty($customerId)) {
            throw new InvalidArgumentException('Pembayaran Bon / Tempo wajib memilih pelanggan terdaftar.');
        }

        $attempts = 0;
        while ($attempts < 3) {
            try {
                return DB::transaction(function () use (
                    $kasir,
                    $cartItems,
                    $total,
                    $bayar,
                    $kembali,
                    $cashShiftId,
                    $metodePembayaran,
                    $customerId,
                    $poinDipakai,
                    $potonganPoin,
                    $dropshipData
                ) {
                    $noTransaksi = $this->generateTransactionNumber();

                    $customer = null;
                    if ($customerId) {
                        /** @var Customer $customer */
                        $customer = Customer::lockForUpdate()->findOrFail($customerId);

                        if ($poinDipakai > 0) {
                            if ($customer->poin < $poinDipakai) {
                                throw new InvalidArgumentException("Poin member tidak mencukupi (tersedia: {$customer->poin} poin).");
                            }
                            $customer->poin -= $poinDipakai;
                        }
                    }

                    $statusPembayaran = 'lunas';
                    $sisaPiutang = 0.0;

                    if ($metodePembayaran === 'tempo') {
                        $statusPembayaran = 'piutang';
                        $sisaPiutang = $total;
                        $bayar = 0.0;
                        $kembali = 0.0;

                        if ($customer->limit_kredit <= 0 || ($customer->piutang_berjalan + $total) > $customer->limit_kredit) {
                            throw new InvalidArgumentException('Limit kredit pelanggan tidak mencukupi untuk transaksi bon/tempo ini.');
                        }

                        $customer->piutang_berjalan += $total;
                    } elseif (in_array($metodePembayaran, ['qris', 'transfer'], true)) {
                        $bayar = $total;
                        $kembali = 0.0;
                    }

                    $poinDidapat = ($customer && $statusPembayaran === 'lunas' && $total >= 10000)
                        ? (int) floor($total / 10000)
                        : 0;

                    if ($customer) {
                        $customer->poin += $poinDidapat;
                        $customer->save();
                    }

                    // 1. Buat record transaksi penjualan utama
                    $sale = Sale::create([
                        'no_transaksi' => $noTransaksi,
                        'kasir_id' => $kasir->id,
                        'cash_shift_id' => $cashShiftId,
                        'customer_id' => $customer?->id,
                        'waktu' => now(),
                        'total' => $total,
                        'bayar' => $bayar,
                        'kembali' => $kembali,
                        'metode_pembayaran' => $metodePembayaran,
                        'status_pembayaran' => $statusPembayaran,
                        'sisa_piutang' => $sisaPiutang,
                        'poin_didapat' => $poinDidapat,
                        'poin_dipakai' => $poinDipakai,
                        'potongan_poin' => $potonganPoin,
                        'is_dropship' => (bool) ($dropshipData['is_dropship'] ?? false),
                        'dropshipper_id' => $dropshipData['dropshipper_id'] ?? null,
                        'dropship_penerima_nama' => $dropshipData['penerima_nama'] ?? null,
                        'dropship_penerima_telepon' => $dropshipData['penerima_telepon'] ?? null,
                        'dropship_penerima_alamat' => $dropshipData['penerima_alamat'] ?? null,
                    ]);

                    // 2. Loop setiap item belanja, kunci record produk, potong stok, dan catat pergerakan stok
                    foreach ($cartItems as $item) {
                        /** @var Product $product */
                        $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                        $qty = (int) $item['qty'];
                        if ($qty <= 0) {
                            throw new InvalidArgumentException("Jumlah item untuk [{$product->nama}] tidak valid.");
                        }

                        if ($product->stok < $qty) {
                            throw new InvalidArgumentException("Stok tidak mencukupi untuk [{$product->nama}]. Stok tersedia: {$product->stok}, diminta: {$qty}.");
                        }

                        $saldoSesudah = $product->stok - $qty;

                        // Kurangi stok etalase terlebih dahulu jika mencukupi, sisanya dari gudang
                        $stokEtalase = $product->stok_etalase;
                        $stokGudang = $product->stok_gudang;

                        if ($stokEtalase >= $qty) {
                            $stokEtalase -= $qty;
                        } else {
                            $sisaQty = $qty - $stokEtalase;
                            $stokEtalase = 0;
                            $stokGudang = max(0, $stokGudang - $sisaQty);
                        }

                        // Update stok produk
                        $product->update([
                            'stok_etalase' => $stokEtalase,
                            'stok_gudang' => $stokGudang,
                            'stok' => $saldoSesudah,
                        ]);

                        // Buat item penjualan
                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'kode_produk' => $product->kode_produk,
                            'nama_produk' => $product->nama,
                            'qty' => $qty,
                            'harga_satuan' => (float) $item['harga_satuan'],
                            'subtotal' => (float) $item['subtotal'],
                        ]);

                        // Catat mutasi di kartu stok
                        StockMovement::create([
                            'product_id' => $product->id,
                            'jenis' => StockMovementType::Penjualan,
                            'jumlah' => -$qty,
                            'saldo_sesudah' => $saldoSesudah,
                            'keterangan' => "Penjualan Kasir No. {$sale->no_transaksi}",
                            'referensi_id' => $sale->id,
                            'referensi_type' => Sale::class,
                        ]);
                    }

                    return $sale->load(['items.product', 'kasir', 'customer', 'cashShift', 'dropshipper']);
                });
            } catch (UniqueConstraintViolationException $e) {
                $attempts++;
                if ($attempts >= 3) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Gagal memproses transaksi setelah beberapa percobaan.');
    }

    /**
     * Cari produk berdasarkan scan barcode dengan fallback penanganan barcode bermasalah:
     * Jika kode 13 digit berawalan 0 tidak ditemukan, coba cari tanpa angka 0 di depan (UPC-A terdeteksi sebagai EAN-13).
     */
    public function findProductByBarcode(string $barcode): ?Product
    {
        $barcode = trim($barcode);
        if (empty($barcode)) {
            return null;
        }

        // 1. Cari exact match
        $product = Product::where('kode_produk', $barcode)->first();
        if ($product) {
            return $product;
        }

        // 2. Fallback UPC-A (13 digit berawalan '0')
        if (strlen($barcode) === 13 && str_starts_with($barcode, '0')) {
            $upcCode = substr($barcode, 1);
            $product = Product::where('kode_produk', $upcCode)->first();
            if ($product) {
                return $product;
            }
        }

        // 3. Fallback sebaliknya: jika input 12 digit (UPC-A), coba cari yang disimpan dengan 13 digit awalan 0
        if (strlen($barcode) === 12) {
            $eanCode = '0'.$barcode;
            $product = Product::where('kode_produk', $eanCode)->first();
            if ($product) {
                return $product;
            }
        }

        return null;
    }
}
