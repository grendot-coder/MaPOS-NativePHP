<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $kode_produk
 * @property string $nama
 * @property string $golongan_id
 * @property string $satuan
 * @property float $harga_beli
 * @property float $harga_retail
 * @property float|null $harga_grosir_1
 * @property float|null $harga_grosir_2
 * @property float|null $harga_grosir_3
 * @property float $diskon
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
        'harga_grosir_2',
        'harga_grosir_3',
        'diskon',
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
            'harga_grosir_2' => 'decimal:2',
            'harga_grosir_3' => 'decimal:2',
            'diskon' => 'decimal:2',
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
}

