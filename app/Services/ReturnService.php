<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReturnService
{
    public function generateSaleReturnNumber(): string
    {
        return $this->generateSequence(SaleReturn::class, 'RTJ-'.Carbon::now()->format('Ymd').'-');
    }

    public function generatePurchaseReturnNumber(): string
    {
        return $this->generateSequence(PurchaseReturn::class, 'RTB-'.Carbon::now()->format('Ymd').'-');
    }

    /**
     * @param  class-string<SaleReturn|PurchaseReturn>  $modelClass
     */
    protected function generateSequence(string $modelClass, string $prefix): string
    {
        $query = $modelClass::withTrashed()
            ->where('no_retur', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(no_retur) DESC, no_retur DESC');

        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $last = $query->first();
        $next = $last ? ((int) substr($last->no_retur, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Proses retur penjualan dari pelanggan dan kembalikan stok etalase secara atomik.
     *
     * @param  array<string, int>  $returnQuantities  [sale_item_id => qty]
     */
    public function processSaleReturn(
        User $user,
        string $saleId,
        array $returnQuantities,
        string $metodeRefund = 'cash',
        ?string $alasan = null,
        ?string $cashShiftId = null
    ): SaleReturn {
        $filtered = array_filter($returnQuantities, fn ($qty) => (int) $qty > 0);
        if (empty($filtered)) {
            throw new InvalidArgumentException('Pilih minimal 1 barang dengan jumlah retur lebih dari 0.');
        }

        if (! in_array($metodeRefund, ['cash', 'potong_piutang'], true)) {
            throw new InvalidArgumentException('Metode pengembalian dana tidak valid.');
        }

        return DB::transaction(function () use ($user, $saleId, $filtered, $metodeRefund, $alasan, $cashShiftId) {
            /** @var Sale $sale */
            $sale = Sale::with('items')->lockForUpdate()->findOrFail($saleId);
            $noRetur = $this->generateSaleReturnNumber();

            $totalRefund = 0.0;
            $preparedItems = [];

            foreach ($filtered as $saleItemId => $qty) {
                $qty = (int) $qty;
                /** @var SaleItem|null $saleItem */
                $saleItem = $sale->items->firstWhere('id', $saleItemId);
                if (! $saleItem) {
                    throw new InvalidArgumentException('Item transaksi tidak ditemukan pada nota ini.');
                }

                $alreadyReturned = (int) SaleReturnItem::where('sale_item_id', $saleItem->id)->sum('qty');
                $maxReturnable = max(0, $saleItem->qty - $alreadyReturned);

                if ($qty > $maxReturnable) {
                    throw new InvalidArgumentException("Jumlah retur [{$saleItem->nama_produk}] melebihi sisa yang dapat diretur ({$maxReturnable}).");
                }

                $effectiveUnitPrice = $saleItem->qty > 0
                    ? ((float) $saleItem->subtotal / $saleItem->qty)
                    : (float) $saleItem->harga_satuan;

                $subtotal = round($effectiveUnitPrice * $qty, 2);
                $totalRefund += $subtotal;

                $preparedItems[] = [
                    'sale_item' => $saleItem,
                    'qty' => $qty,
                    'harga_satuan' => $effectiveUnitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            $saleReturn = SaleReturn::create([
                'no_retur' => $noRetur,
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'cash_shift_id' => $metodeRefund === 'cash' ? $cashShiftId : null,
                'metode_refund' => $metodeRefund,
                'total_refund' => $totalRefund,
                'alasan' => filled($alasan) ? trim($alasan) : null,
                'waktu' => now(),
            ]);

            foreach ($preparedItems as $row) {
                /** @var SaleItem $saleItem */
                $saleItem = $row['sale_item'];
                $qty = $row['qty'];

                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($saleItem->product_id);
                $product->stok_etalase += $qty;
                $product->stok = $product->stok_etalase + $product->stok_gudang;
                $product->save();

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $product->id,
                    'nama_produk' => $saleItem->nama_produk,
                    'qty' => $qty,
                    'harga_satuan' => $row['harga_satuan'],
                    'subtotal' => $row['subtotal'],
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'jenis' => StockMovementType::Retur,
                    'jumlah' => $qty,
                    'saldo_sesudah' => $product->stok,
                    'keterangan' => "Retur Penjualan {$noRetur} (Nota {$sale->no_transaksi})",
                    'referensi_id' => $saleReturn->id,
                    'referensi_type' => SaleReturn::class,
                ]);
            }

            if ($metodeRefund === 'potong_piutang' && $sale->status_pembayaran === 'piutang' && $sale->customer_id) {
                /** @var Customer $customer */
                $customer = Customer::lockForUpdate()->findOrFail($sale->customer_id);
                $potong = min((float) $sale->sisa_piutang, $totalRefund);

                $sisaBaru = max(0.0, (float) $sale->sisa_piutang - $potong);
                $sale->update([
                    'sisa_piutang' => $sisaBaru,
                    'status_pembayaran' => $sisaBaru <= 0 ? 'lunas' : 'piutang',
                ]);

                $customer->piutang_berjalan = max(0.0, (float) $customer->piutang_berjalan - $potong);
                $customer->save();
            }

            return $saleReturn->load(['items', 'sale']);
        });
    }

    /**
     * Proses retur pembelian ke supplier dan kurangi stok secara atomik.
     *
     * @param  array<string, int>  $returnQuantities  [purchase_item_id => qty]
     */
    public function processPurchaseReturn(
        User $user,
        string $purchaseId,
        array $returnQuantities,
        string $metodePengembalian = 'potong_hutang',
        ?string $alasan = null
    ): PurchaseReturn {
        $filtered = array_filter($returnQuantities, fn ($qty) => (int) $qty > 0);
        if (empty($filtered)) {
            throw new InvalidArgumentException('Pilih minimal 1 barang dengan jumlah retur lebih dari 0.');
        }

        if (! in_array($metodePengembalian, ['potong_hutang', 'refund_tunai'], true)) {
            throw new InvalidArgumentException('Metode penyelesaian retur supplier tidak valid.');
        }

        return DB::transaction(function () use ($user, $purchaseId, $filtered, $metodePengembalian, $alasan) {
            /** @var Purchase $purchase */
            $purchase = Purchase::with('items')->lockForUpdate()->findOrFail($purchaseId);
            $noRetur = $this->generatePurchaseReturnNumber();

            $totalRetur = 0.0;
            $preparedItems = [];

            foreach ($filtered as $purchaseItemId => $qty) {
                $qty = (int) $qty;
                /** @var PurchaseItem|null $purchaseItem */
                $purchaseItem = $purchase->items->firstWhere('id', $purchaseItemId);
                if (! $purchaseItem) {
                    throw new InvalidArgumentException('Item faktur tidak ditemukan.');
                }

                $alreadyReturned = (int) PurchaseReturnItem::where('purchase_item_id', $purchaseItem->id)->sum('qty');
                $maxReturnable = max(0, $purchaseItem->qty - $alreadyReturned);

                if ($qty > $maxReturnable) {
                    throw new InvalidArgumentException("Jumlah retur [{$purchaseItem->nama_produk}] melebihi sisa faktur ({$maxReturnable}).");
                }

                $subtotal = round((float) $purchaseItem->harga_beli * $qty, 2);
                $totalRetur += $subtotal;

                $preparedItems[] = [
                    'purchase_item' => $purchaseItem,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                ];
            }

            $purchaseReturn = PurchaseReturn::create([
                'no_retur' => $noRetur,
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
                'metode_pengembalian' => $metodePengembalian,
                'total_retur' => $totalRetur,
                'alasan' => filled($alasan) ? trim($alasan) : null,
                'waktu' => now(),
            ]);

            foreach ($preparedItems as $row) {
                /** @var PurchaseItem $purchaseItem */
                $purchaseItem = $row['purchase_item'];
                $qty = $row['qty'];

                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($purchaseItem->product_id);
                if ($product->stok < $qty) {
                    throw new InvalidArgumentException("Stok saat ini [{$product->nama}] ({$product->stok}) tidak mencukupi untuk retur {$qty} unit.");
                }

                $stokGudang = $product->stok_gudang;
                $stokEtalase = $product->stok_etalase;

                if ($purchaseItem->lokasi_tujuan === 'etalase') {
                    if ($stokEtalase >= $qty) {
                        $stokEtalase -= $qty;
                    } else {
                        $sisa = $qty - $stokEtalase;
                        $stokEtalase = 0;
                        $stokGudang = max(0, $stokGudang - $sisa);
                    }
                } else {
                    if ($stokGudang >= $qty) {
                        $stokGudang -= $qty;
                    } else {
                        $sisa = $qty - $stokGudang;
                        $stokGudang = 0;
                        $stokEtalase = max(0, $stokEtalase - $sisa);
                    }
                }

                $product->update([
                    'stok_gudang' => $stokGudang,
                    'stok_etalase' => $stokEtalase,
                    'stok' => $stokGudang + $stokEtalase,
                ]);

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $product->id,
                    'nama_produk' => $purchaseItem->nama_produk,
                    'qty' => $qty,
                    'harga_beli' => (float) $purchaseItem->harga_beli,
                    'subtotal' => $row['subtotal'],
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'jenis' => StockMovementType::Retur,
                    'jumlah' => -$qty,
                    'saldo_sesudah' => $product->stok,
                    'keterangan' => "Retur Pembelian {$noRetur} (Faktur {$purchase->no_faktur})",
                    'referensi_id' => $purchaseReturn->id,
                    'referensi_type' => PurchaseReturn::class,
                ]);
            }

            if ($metodePengembalian === 'potong_hutang' && $purchase->status_pembayaran === 'hutang') {
                $sisaHutang = $purchase->sisa_hutang;
                $potong = min($sisaHutang, $totalRetur);

                if ($potong > 0) {
                    PurchasePayment::create([
                        'purchase_id' => $purchase->id,
                        'user_id' => $user->id,
                        'jumlah' => $potong,
                        'metode' => 'retur',
                        'catatan' => "Potongan Retur {$noRetur}",
                        'waktu' => now(),
                    ]);

                    $dibayarBaru = (float) $purchase->dibayar + $potong;
                    $purchase->update([
                        'dibayar' => $dibayarBaru,
                        'status_pembayaran' => ($purchase->total - $dibayarBaru) <= 0 ? 'lunas' : 'hutang',
                    ]);
                }
            }

            return $purchaseReturn->load(['items', 'purchase']);
        });
    }
}
