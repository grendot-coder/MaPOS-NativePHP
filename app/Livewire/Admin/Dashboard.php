<?php

namespace App\Livewire\Admin;

use App\Models\Golongan;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin Dashboard - MaPOS')]
class Dashboard extends Component
{
    public function render()
    {
        $totalProduk = Product::count();
        $totalGolongan = Golongan::count();
        $stokMenipisCount = Product::whereColumn('stok', '<=', 'min_stok')->count();
        $totalNilaiAset = Product::sum(DB::raw('stok * harga_beli'));

        $lowStockProducts = Product::with('golongan')
            ->whereColumn('stok', '<=', 'min_stok')
            ->orderBy('stok', 'asc')
            ->limit(5)
            ->get();

        $recentMovements = StockMovement::with('product')
            ->latest()
            ->limit(5)
            ->get();

        return view('livewire.admin.dashboard', [
            'totalProduk' => $totalProduk,
            'totalGolongan' => $totalGolongan,
            'stokMenipisCount' => $stokMenipisCount,
            'totalNilaiAset' => $totalNilaiAset,
            'lowStockProducts' => $lowStockProducts,
            'recentMovements' => $recentMovements,
        ]);
    }
}

