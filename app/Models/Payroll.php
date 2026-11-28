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
 * @property string $no_slip
 * @property string $employee_id
 * @property int $user_id
 * @property string $periode_bulan
 * @property float $gaji_pokok
 * @property float $tunjangan
 * @property float $bonus
 * @property float $potongan_kasbon
 * @property float $potongan_lain
 * @property float $total_diterima
 * @property string $status
 * @property Carbon|null $tanggal_bayar
 * @property string|null $catatan
 */
class Payroll extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'payrolls';

    protected $fillable = [
        'no_slip',
        'employee_id',
        'user_id',
        'periode_bulan',
        'gaji_pokok',
        'tunjangan',
        'bonus',
        'potongan_kasbon',
        'potongan_lain',
        'total_diterima',
        'status',
        'tanggal_bayar',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'float',
            'tunjangan' => 'float',
            'bonus' => 'float',
            'potongan_kasbon' => 'float',
            'potongan_lain' => 'float',
            'total_diterima' => 'float',
            'tanggal_bayar' => 'date',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<Pengeluaran, $this> */
    public function kasbonItems(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'payroll_id');
    }
}
