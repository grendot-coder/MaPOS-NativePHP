<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $kode_dropshipper
 * @property string $nama
 * @property string|null $telepon
 * @property string|null $alamat
 * @property float $diskon_khusus
 * @property bool $status_aktif
 */
class Dropshipper extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'dropshippers';

    protected $fillable = [
        'kode_dropshipper',
        'nama',
        'telepon',
        'alamat',
        'diskon_khusus',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'diskon_khusus' => 'float',
            'status_aktif' => 'boolean',
        ];
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'dropshipper_id');
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()
            ->where('kode_dropshipper', 'like', 'DRP-%')
            ->orderByRaw('LENGTH(kode_dropshipper) DESC, kode_dropshipper DESC')
            ->first();

        $next = $last ? ((int) substr($last->kode_dropshipper, 4)) + 1 : 1;

        return 'DRP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
