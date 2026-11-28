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
 * @property string $no_retur
 * @property string $sale_id
 * @property int $user_id
 * @property string|null $cash_shift_id
 * @property string $metode_refund
 * @property float $total_refund
 * @property string|null $alasan
 * @property Carbon $waktu
 */
class SaleReturn extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'sale_returns';

    protected $fillable = [
        'no_retur',
        'sale_id',
        'user_id',
        'cash_shift_id',
        'metode_refund',
        'total_refund',
        'alasan',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'total_refund' => 'float',
            'waktu' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<SaleReturnItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class, 'sale_return_id');
    }
}
