<?php

namespace App\Enums;

enum PengeluaranKategori: string
{
    case Operasional = 'operasional';
    case Kasbon = 'kasbon';
    case Gaji = 'gaji';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Operasional => 'Biaya Operasional Toko',
            self::Kasbon => 'Kasbon Karyawan',
            self::Gaji => 'Penggajian Karyawan',
            self::Lainnya => 'Lain-lain',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Operasional => 'amber',
            self::Kasbon => 'rose',
            self::Gaji => 'blue',
            self::Lainnya => 'zinc',
        };
    }
}
