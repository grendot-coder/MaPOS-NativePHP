<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $kode_jabatan
 * @property string $nama_jabatan
 * @property float $gaji_pokok_default
 * @property float $tunjangan_default
 * @property string|null $deskripsi
 */
class Jabatan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'jabatans';

    protected $fillable = [
        'kode_jabatan',
        'nama_jabatan',
        'gaji_pokok_default',
        'tunjangan_default',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok_default' => 'float',
            'tunjangan_default' => 'float',
        ];
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'jabatan_id');
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()
            ->where('kode_jabatan', 'like', 'JBT-%')
            ->orderByRaw('LENGTH(kode_jabatan) DESC, kode_jabatan DESC')
            ->first();

        $next = $last ? ((int) substr($last->kode_jabatan, 4)) + 1 : 1;

        return 'JBT-'.str_pad((string) $next, 2, '0', STR_PAD_LEFT);
    }
}
