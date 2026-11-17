<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Generate nomor referensi faktur pembelian internal harian.
     * Format: PB-YYYYMMDD-0001
     */
    public function generateInvoiceNumber(): string
    {
        $todayPrefix = 'PB-' . Carbon::now()->format('Ymd') . '-';

        $lastPurchase = Purchase::withTrashed()
            ->where('no_faktur', 'like', "{$todayPrefix}%")
            ->orderBy('no_faktur', 'desc')
            ->first();

        if ($lastPurchase) {
            $lastNumber = (int) substr($lastPurchase->no_faktur, strlen($todayPrefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $todayPrefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Catat faktur pembelian, tambah stok produk, dan log mutasi stok dalam transaksi atomik.
     *
     * @param  array{
     *     no_faktur: string,
     *     supplier_id?: string|null,
     *     nama_supplier: string,
     *     tanggal: string,
     *     status_pembayaran?: string,
     *     catatan?: string|null
     * }  $headerData
     * @param  array<int, array{
     *     product_id: string,
     *     qty: int,
     *     lokasi_tujuan?: string,
     *     harga_beli: float,
     *     update_harga_beli_master?: bool
     * }>  $items
     */
    public function recordPurchase(User $user, array $headerData, array $items): Purchase
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('Daftar barang kulakan tidak boleh kosong.');
        }

        if (empty(trim($headerData['no_faktur'] ?? ''))) {
            throw new \InvalidArgumentException('Nomor faktur pembelian wajib diisi.');
        }

        return DB::transaction(function () use ($user, $headerData, $items) {
            $total = 0;
            foreach ($items as $item) {
                $qty = (int) $item['qty'];
                $hargaBeli = (float) $item['harga_beli'];
                if ($qty <= 0) {
                    throw new \InvalidArgumentException('Jumlah barang (qty) minimal 1 unit.');
                }
                if ($hargaBeli < 0) {
                    throw new \InvalidArgumentException('Harga modal beli tidak boleh negatif.');
                }
                $total += ($qty * $hargaBeli);
            }

            $purchase = Purchase::create([
                'no_faktur' => trim($headerData['no_faktur']),
                'supplier_id' => $headerData['supplier_id'] ?? null,
                'nama_supplier' => trim($headerData['nama_supplier'] ?? 'Supplier Umum'),
                'user_id' => $user->id,
                'tanggal' => Carbon::parse($headerData['tanggal'] ?? now()),
                'total' => $total,
                'status_pembayaran' => $headerData['status_pembayaran'] ?? 'lunas',
                'catatan' => $headerData['catatan'] ?? null,
            ]);

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                $qty = (int) $item['qty'];
                $hargaBeli = (float) $item['harga_beli'];
                $subtotal = $qty * $hargaBeli;
                $lokasi = $item['lokasi_tujuan'] ?? 'gudang';
                $updateMaster = (bool) ($item['update_harga_beli_master'] ?? true);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'nama_produk' => $product->nama,
                    'qty' => $qty,
                    'lokasi_tujuan' => $lokasi,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $subtotal,
                    'update_harga_beli_master' => $updateMaster,
                ]);

                // Update stok produk
                if ($lokasi === 'etalase') {
                    $product->stok_etalase += $qty;
                } else {
                    $product->stok_gudang += $qty;
                }
                $product->stok = $product->stok_etalase + $product->stok_gudang;

                // Perbarui harga beli master jika dipilih
                if ($updateMaster) {
                    $product->harga_beli = $hargaBeli;
                }
                $product->save();

                // Catat mutasi stok
                StockMovement::create([
                    'product_id' => $product->id,
                    'jenis' => StockMovementType::Pembelian,
                    'jumlah' => $qty,
                    'saldo_sesudah' => $product->stok,
                    'keterangan' => "Pembelian Faktur #{$purchase->no_faktur} ({$purchase->nama_supplier})",
                    'referensi_id' => $purchase->id,
                    'referensi_type' => Purchase::class,
                ]);
            }

            return $purchase;
        });
    }
}
