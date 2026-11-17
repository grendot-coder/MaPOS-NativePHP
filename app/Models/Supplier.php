<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $nama
 * @property string|null $telepon
 * @property string|null $alamat
 * @property string|null $nama_kontak
 * @property string|null $catatan
 */
class Supplier extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'nama',
        'telepon',
        'alamat',
        'nama_kontak',
        'catatan',
    ];

    /** @return HasMany<Purchase, $this> */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }
}
