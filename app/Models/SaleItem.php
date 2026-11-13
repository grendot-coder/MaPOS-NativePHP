<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $sale_id
 * @property string $product_id
 * @property string $kode_produk
 * @property string $nama_produk
 * @property int $qty
 * @property float $harga_satuan
 * @property float $subtotal
 */
class SaleItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'kode_produk',
        'nama_produk',
        'qty',
        'harga_satuan',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
