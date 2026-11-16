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
 * @property int $user_id
 * @property \Carbon\Carbon $waktu_buka
 * @property \Carbon\Carbon|null $waktu_tutup
 * @property float $saldo_awal
 * @property float $saldo_akhir_sistem
 * @property float|null $saldo_akhir_fisik
 * @property float $selisih
 * @property string $status
 * @property string|null $catatan
 */
class CashShift extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cash_shifts';

    protected $fillable = [
        'user_id',
        'waktu_buka',
        'waktu_tutup',
        'saldo_awal',
        'saldo_akhir_sistem',
        'saldo_akhir_fisik',
        'selisih',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_buka' => 'datetime',
            'waktu_tutup' => 'datetime',
            'saldo_awal' => 'float',
            'saldo_akhir_sistem' => 'float',
            'saldo_akhir_fisik' => 'float',
            'selisih' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cash_shift_id');
    }

    public function pengeluarans(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'cash_shift_id');
    }

    public function getTotalPenjualanAttribute(): float
    {
        return (float) $this->sales()->sum('total');
    }

    public function getTotalPengeluaranAttribute(): float
    {
        return (float) $this->pengeluarans()->sum('nominal');
    }

    public function getSaldoSaatIniAttribute(): float
    {
        return (float) ($this->saldo_awal + $this->total_penjualan - $this->total_pengeluaran);
    }

    public function getStatusSelisihAttribute(): string
    {
        if ($this->selisih == 0) {
            return 'pas';
        }

        return $this->selisih > 0 ? 'lebih' : 'kurang';
    }

    public function scopeActiveShift($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('status', 'open')
            ->latest('waktu_buka');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
