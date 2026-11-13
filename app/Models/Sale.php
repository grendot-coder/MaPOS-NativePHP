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
 * @property string $no_transaksi
 * @property int $kasir_id
 * @property string $waktu
 * @property float $total
 * @property float $bayar
 * @property float $kembali
 */
class Sale extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'sales';

    protected $fillable = [
        'no_transaksi',
        'kasir_id',
        'waktu',
        'total',
        'bayar',
        'kembali',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'total' => 'decimal:2',
            'bayar' => 'decimal:2',
            'kembali' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }
}
