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
 * @property string $no_transaksi
 * @property int $kasir_id
 * @property string|null $cash_shift_id
 * @property string|null $customer_id
 * @property string $waktu
 * @property float $total
 * @property float $bayar
 * @property float $kembali
 * @property string $metode_pembayaran
 * @property string $status_pembayaran
 * @property float $sisa_piutang
 * @property int $poin_didapat
 * @property int $poin_dipakai
 * @property float $potongan_poin
 */
class Sale extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'sales';

    protected $fillable = [
        'no_transaksi',
        'kasir_id',
        'cash_shift_id',
        'customer_id',
        'waktu',
        'total',
        'bayar',
        'kembali',
        'metode_pembayaran',
        'status_pembayaran',
        'sisa_piutang',
        'poin_didapat',
        'poin_dipakai',
        'potongan_poin',
        'is_dropship',
        'dropshipper_id',
        'dropship_penerima_nama',
        'dropship_penerima_telepon',
        'dropship_penerima_alamat',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'total' => 'decimal:2',
            'bayar' => 'decimal:2',
            'kembali' => 'decimal:2',
            'sisa_piutang' => 'decimal:2',
            'poin_didapat' => 'integer',
            'poin_dipakai' => 'integer',
            'potongan_poin' => 'decimal:2',
            'is_dropship' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /** @return BelongsTo<Dropshipper, $this> */
    public function dropshipper(): BelongsTo
    {
        return $this->belongsTo(Dropshipper::class, 'dropshipper_id');
    }

    /** @return HasMany<SalePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class, 'sale_id');
    }

    /** @return HasMany<SaleReturn, $this> */
    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class, 'sale_id');
    }

    /** @return BelongsTo<CashShift, $this> */
    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'cash_shift_id');
    }

    /** @return BelongsTo<User, $this> */
    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    /**
     * Nama kasir/petugas shift yang dicetak pada struk dan laporan.
     */
    public function getNamaKasirStrukAttribute(): string
    {
        if ($this->cashShift && filled($this->cashShift->nama_petugas)) {
            return $this->cashShift->nama_petugas;
        }

        return $this->kasir->name ?? 'Kasir';
    }
}
