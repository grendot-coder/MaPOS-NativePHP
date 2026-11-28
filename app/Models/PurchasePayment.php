<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $purchase_id
 * @property int $user_id
 * @property float $jumlah
 * @property string $metode
 * @property string|null $catatan
 * @property Carbon $waktu
 */
class PurchasePayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'purchase_payments';

    protected $fillable = [
        'purchase_id',
        'user_id',
        'jumlah',
        'metode',
        'catatan',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
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
}
