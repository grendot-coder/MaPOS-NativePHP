<?php

namespace App\Livewire\Kasir;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Kasir POS - MaPOS')]
class PosIndex extends Component
{
    public function render()
    {
        $totalProdukSiapJual = Product::where('stok', '>', 0)->count();

        return view('livewire.kasir.pos-index', [
            'totalProdukSiapJual' => $totalProdukSiapJual,
        ]);
    }
}
