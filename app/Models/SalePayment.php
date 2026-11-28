<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $sale_id
 * @property string $customer_id
 * @property int $user_id
 * @property float $jumlah
 * @property string $metode
 * @property string|null $catatan
 * @property Carbon $waktu
 */
class SalePayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sale_payments';

    protected $fillable = [
        'sale_id',
        'customer_id',
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

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
