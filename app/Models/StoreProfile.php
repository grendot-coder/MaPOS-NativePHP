<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nama_toko
 * @property string|null $slogan
 * @property string|null $alamat
 * @property string|null $telepon
 * @property string|null $email
 * @property string|null $website
 * @property string|null $footer_struk
 * @property string $ukuran_kertas
 * @property string|null $logo_path
 */
class StoreProfile extends Model
{
    use HasFactory;

    protected $table = 'store_profiles';

    protected $fillable = [
        'nama_toko',
        'slogan',
        'alamat',
        'telepon',
        'email',
        'website',
        'footer_struk',
        'ukuran_kertas',
        'logo_path',
    ];

    /**
     * Dapatkan instance profil toko tunggal. Jika belum ada di database,
     * buat record default standar retail MaPOS secara otomatis.
     */
    public static function getProfile(): self
    {
        return static::firstOrCreate([], [
            'nama_toko' => 'MAPOS MINIMARKET',
            'slogan' => 'Sistem Kasir & Manajemen POS',
            'alamat' => 'Jl. Soedirman No. 10',
            'telepon' => '(085) 555-555-555',
            'email' => 'toko@mapos.local',
            'website' => null,
            'footer_struk' => 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan',
            'ukuran_kertas' => '58mm',
        ]);
    }
}
