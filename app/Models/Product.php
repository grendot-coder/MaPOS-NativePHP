<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $kode_produk
 * @property string $nama
 * @property string $golongan_id
 * @property string $satuan
 * @property float $harga_beli
 * @property float $harga_retail
 * @property float|null $harga_grosir_1
 * @property int|null $min_qty_grosir_1
 * @property float|null $harga_grosir_2
 * @property int|null $min_qty_grosir_2
 * @property float|null $harga_grosir_3
 * @property int|null $min_qty_grosir_3
 * @property float $diskon
 * @property Carbon|null $promo_mulai
 * @property Carbon|null $promo_sampai
 * @property int $stok_etalase
 * @property int $stok_gudang
 * @property int $stok
 * @property int $min_stok
 */
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'kode_produk',
        'nama',
        'golongan_id',
        'satuan',
        'harga_beli',
        'harga_retail',
        'harga_grosir_1',
        'min_qty_grosir_1',
        'harga_grosir_2',
        'min_qty_grosir_2',
        'harga_grosir_3',
        'min_qty_grosir_3',
        'diskon',
        'promo_mulai',
        'promo_sampai',
        'stok_etalase',
        'stok_gudang',
        'stok',
        'min_stok',
    ];

    protected function casts(): array
    {
        return [
            'harga_beli' => 'decimal:2',
            'harga_retail' => 'decimal:2',
            'harga_grosir_1' => 'decimal:2',
            'min_qty_grosir_1' => 'integer',
            'harga_grosir_2' => 'decimal:2',
            'min_qty_grosir_2' => 'integer',
            'harga_grosir_3' => 'decimal:2',
            'min_qty_grosir_3' => 'integer',
            'diskon' => 'decimal:2',
            'promo_mulai' => 'date',
            'promo_sampai' => 'date',
            'stok_etalase' => 'integer',
            'stok_gudang' => 'integer',
            'stok' => 'integer',
            'min_stok' => 'integer',
        ];
    }

    /** @return BelongsTo<Golongan, $this> */
    public function golongan(): BelongsTo
    {
        return $this->belongsTo(Golongan::class, 'golongan_id');
    }

    /** @return HasMany<SaleItem, $this> */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    /**
     * Tentukan harga satuan berdasarkan kuantitas pembelian dan ambang qty grosir produk.
     *
     * @return array{harga: float, tier: ?string}
     */
    public function resolveUnitPriceForQty(int $qty): array
    {
        $tiers = [];

        foreach ([1, 2, 3] as $level) {
            $harga = $this->{"harga_grosir_{$level}"};
            $minQty = $this->{"min_qty_grosir_{$level}"};

            if ($harga !== null && (float) $harga > 0 && $minQty !== null && (int) $minQty >= 2 && $qty >= (int) $minQty) {
                $tiers[] = [
                    'min_qty' => (int) $minQty,
                    'harga' => (float) $harga,
                    'tier' => "Grosir {$level}",
                ];
            }
        }

        if (! empty($tiers)) {
            usort($tiers, fn (array $a, array $b): int => $b['min_qty'] <=> $a['min_qty']);

            return [
                'harga' => $tiers[0]['harga'],
                'tier' => $tiers[0]['tier'],
            ];
        }

        return [
            'harga' => (float) $this->harga_retail,
            'tier' => null,
        ];
    }

    /**
     * Hitung persentase diskon aktif (memperhatikan periode promo jika diatur).
     */
    public function resolveEffectiveDiscount(): float
    {
        $diskon = (float) $this->diskon;
        if ($diskon <= 0) {
            return 0.0;
        }

        $today = now()->startOfDay();

        if ($this->promo_mulai && $today->lt($this->promo_mulai->startOfDay())) {
            return 0.0;
        }

        if ($this->promo_sampai && $today->gt($this->promo_sampai->startOfDay())) {
            return 0.0;
        }

        return $diskon;
    }
}
