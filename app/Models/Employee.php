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
 * @property string $kode_karyawan
 * @property string $jabatan_id
 * @property int|null $user_id
 * @property string $nama
 * @property string|null $telepon
 * @property string|null $alamat
 * @property Carbon|null $tanggal_masuk
 * @property float $gaji_pokok
 * @property float $tunjangan
 * @property bool $status_aktif
 */
class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'kode_karyawan',
        'jabatan_id',
        'user_id',
        'nama',
        'telepon',
        'alamat',
        'tanggal_masuk',
        'gaji_pokok',
        'tunjangan',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'gaji_pokok' => 'float',
            'tunjangan' => 'float',
            'status_aktif' => 'boolean',
        ];
    }

    /** @return BelongsTo<Jabatan, $this> */
    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<Payroll, $this> */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    /** @return HasMany<Pengeluaran, $this> */
    public function pengeluarans(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'employee_id');
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()
            ->where('kode_karyawan', 'like', 'EMP-%')
            ->orderByRaw('LENGTH(kode_karyawan) DESC, kode_karyawan DESC')
            ->first();

        $next = $last ? ((int) substr($last->kode_karyawan, 4)) + 1 : 1;

        return 'EMP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
