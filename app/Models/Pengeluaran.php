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
        'employee_id',
        'payroll_id',
        'nominal',
        'keterangan',
        'user_id',
        'cash_shift_id',
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

    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'cash_shift_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }
}
