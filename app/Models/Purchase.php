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
 * @property string $no_faktur
 * @property string|null $supplier_id
 * @property string $nama_supplier
 * @property int $user_id
 * @property \Carbon\Carbon $tanggal
 * @property float $total
 * @property string $status_pembayaran
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
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total' => 'float',
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

    public function getTotalPcsAttribute(): int
    {
        return (int) $this->items()->sum('qty');
    }
}
