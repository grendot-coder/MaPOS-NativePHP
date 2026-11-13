<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $product_id
 * @property StockMovementType $jenis
 * @property int $jumlah
 * @property int $saldo_sesudah
 * @property string|null $keterangan
 * @property string|null $referensi_id
 * @property string|null $referensi_type
 */
class StockMovement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id',
        'jenis',
        'jumlah',
        'saldo_sesudah',
        'keterangan',
        'referensi_id',
        'referensi_type',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => StockMovementType::class,
            'jumlah' => 'integer',
            'saldo_sesudah' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
