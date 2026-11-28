<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $no_faktur
 * @property string|null $supplier_id
 * @property string $nama_supplier
 * @property int $user_id
 * @property Carbon $tanggal
 * @property float $total
 * @property string $status_pembayaran
 * @property Carbon|null $jatuh_tempo
 * @property float $dibayar
 * @property string|null $catatan
 */
class Purchase extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'purchases';

    protected $fillable = [
        'no_faktur',
        'supplier_id',
        'nama_supplier',
        'user_id',
        'tanggal',
        'total',
        'status_pembayaran',
        'jatuh_tempo',
        'dibayar',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jatuh_tempo' => 'date',
            'total' => 'float',
            'dibayar' => 'float',
        ];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<PurchaseItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /** @return HasMany<PurchasePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class, 'purchase_id');
    }

    /** @return HasMany<PurchaseReturn, $this> */
    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_id');
    }

    public function getSisaHutangAttribute(): float
    {
        if ($this->status_pembayaran === 'lunas') {
            return 0.0;
        }

        return max(0.0, (float) $this->total - (float) $this->dibayar);
    }

    public function getTotalPcsAttribute(): int
    {
        return (int) $this->items()->sum('qty');
    }
}
