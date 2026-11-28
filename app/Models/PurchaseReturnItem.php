<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $purchase_return_id
 * @property string $purchase_item_id
 * @property string $product_id
 * @property string $nama_produk
 * @property int $qty
 * @property float $harga_beli
 * @property float $subtotal
 */
class PurchaseReturnItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'purchase_return_items';

    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'product_id',
        'nama_produk',
        'qty',
        'harga_beli',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_beli' => 'float',
            'subtotal' => 'float',
        ];
    }

    /** @return BelongsTo<PurchaseReturn, $this> */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
