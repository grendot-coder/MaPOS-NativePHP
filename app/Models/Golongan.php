<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $nama_golongan
 * @property string $kode_golongan
 */
class Golongan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'golongans';

    protected $fillable = [
        'nama_golongan',
        'kode_golongan',
    ];

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'golongan_id');
    }
}
