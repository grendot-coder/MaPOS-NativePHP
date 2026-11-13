<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Catat pergerakan stok dan update stok produk secara atomik.
     * $jumlah: positif untuk penambahan, negatif untuk pengurangan.
     */
    public function recordMovement(
        Product $product,
        StockMovementType $type,
        int $jumlah,
        ?string $keterangan = null,
        ?Model $reference = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $jumlah, $keterangan, $reference) {
            // Lock record produk untuk konsistensi
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);

            $saldoSesudah = $lockedProduct->stok + $jumlah;

            if ($saldoSesudah < 0) {
                throw new \InvalidArgumentException("Stok tidak mencukupi untuk produk [{$lockedProduct->nama}]. Stok tersedia: {$lockedProduct->stok}, dibutuhkan: " . abs($jumlah));
            }

            // Update stok pada produk
            $lockedProduct->update([
                'stok' => $saldoSesudah,
            ]);

            // Buat record stock movement
            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'jenis' => $type,
                'jumlah' => $jumlah,
                'saldo_sesudah' => $saldoSesudah,
                'keterangan' => $keterangan,
                'referensi_id' => $reference?->getKey(),
                'referensi_type' => $reference ? get_class($reference) : null,
            ]);
        });
    }

    /**
     * Penyesuaian stok manual (stock opname / koreksi stok oleh Admin).
     */
    public function adjustStock(Product $product, int $newStock, string $keterangan): StockMovement
    {
        return DB::transaction(function () use ($product, $newStock, $keterangan) {
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);
            $selisih = $newStock - $lockedProduct->stok;

            return $this->recordMovement(
                $lockedProduct,
                StockMovementType::Penyesuaian,
                $selisih,
                $keterangan
            );
        });
    }
}
