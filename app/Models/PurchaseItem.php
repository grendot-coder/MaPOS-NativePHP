<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $purchase_id
 * @property string $product_id
 * @property string $nama_produk
 * @property int $qty
 * @property string $lokasi_tujuan
 * @property float $harga_beli
 * @property float $subtotal
 * @property bool $update_harga_beli_master
 */
class PurchaseItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'product_id',
        'nama_produk',
        'qty',
        'lokasi_tujuan',
        'harga_beli',
        'subtotal',
        'update_harga_beli_master',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_beli' => 'float',
            'subtotal' => 'float',
            'update_harga_beli_master' => 'boolean',
        ];
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
