<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $kode_member
 * @property string $nama
 * @property string|null $telepon
 * @property string|null $alamat
 * @property int $poin
 * @property float $limit_kredit
 * @property float $piutang_berjalan
 */
class Customer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'kode_member',
        'nama',
        'telepon',
        'alamat',
        'poin',
        'limit_kredit',
        'piutang_berjalan',
    ];

    protected function casts(): array
    {
        return [
            'poin' => 'integer',
            'limit_kredit' => 'float',
            'piutang_berjalan' => 'float',
        ];
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    /** @return HasMany<SalePayment, $this> */
    public function salePayments(): HasMany
    {
        return $this->hasMany(SalePayment::class, 'customer_id');
    }

    public function getSisaLimitKreditAttribute(): float
    {
        return max(0.0, (float) $this->limit_kredit - (float) $this->piutang_berjalan);
    }

    public static function generateMemberCode(): string
    {
        $last = static::withTrashed()
            ->where('kode_member', 'like', 'MBR-%')
            ->orderByRaw('LENGTH(kode_member) DESC, kode_member DESC')
            ->first();

        $next = $last ? ((int) substr($last->kode_member, 4)) + 1 : 1;

        return 'MBR-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
