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
 * @property string $purchase_id
 * @property int $user_id
 * @property string $metode_pengembalian
 * @property float $total_retur
 * @property string|null $alasan
 * @property Carbon $waktu
 */
class PurchaseReturn extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'no_retur',
        'purchase_id',
        'user_id',
        'metode_pengembalian',
        'total_retur',
        'alasan',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'total_retur' => 'float',
            'waktu' => 'datetime',
        ];
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<PurchaseReturnItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id');
    }
}
