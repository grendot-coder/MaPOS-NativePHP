<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Penjualan = 'penjualan';
    case Penyesuaian = 'penyesuaian';
    case Pembelian = 'pembelian';
    case Retur = 'retur';

    public function label(): string
    {
        return match ($this) {
            StockMovementType::Penjualan => 'Penjualan',
            StockMovementType::Penyesuaian => 'Penyesuaian Stok',
            StockMovementType::Pembelian => 'Pembelian',
            StockMovementType::Retur => 'Retur',
        };
    }
}
