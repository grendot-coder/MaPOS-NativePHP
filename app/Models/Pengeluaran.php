<?php

namespace App\Models;

use App\Enums\PengeluaranKategori;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengeluaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'no_referensi',
        'kategori',
        'nama_karyawan',
        'nominal',
        'keterangan',
        'user_id',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'kategori' => PengeluaranKategori::class,
            'nominal' => 'float',
            'waktu' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
