<?php

namespace App\Livewire\Admin;

use App\Models\Golongan;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan Stok & Nilai Aset - MaPOS')]
class LaporanStok extends Component
{
    use WithPagination;

    public string $search = '';

    public string $selectedGolongan = '';

    #[Url(as: 'status')]
    public string $statusFilter = ''; // 'all', 'menipis', 'habis', 'aman'

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedGolongan(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // 1. Hitung ringkasan aset untuk seluruh produk di database
        $allProducts = Product::all();
        $totalNilaiModal = $allProducts->sum(fn ($p) => (float) $p->harga_beli * (int) $p->stok);
        $totalNilaiRetail = $allProducts->sum(fn ($p) => (float) $p->harga_retail * (int) $p->stok);
        $totalPotensiLaba = max(0, $totalNilaiRetail - $totalNilaiModal);

        $totalMenipis = $allProducts->filter(fn ($p) => $p->stok > 0 && $p->stok <= $p->min_stok)->count();
        $totalHabis = $allProducts->filter(fn ($p) => $p->stok <= 0)->count();

        // 2. Query data dengan filter
        $query = Product::with('golongan');

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nama', 'like', "%{$this->search}%")
                    ->orWhere('kode_produk', 'like', "%{$this->search}%");
            });
        }

        if (! empty($this->selectedGolongan)) {
            $query->where('golongan_id', $this->selectedGolongan);
        }

        if ($this->statusFilter === 'menipis') {
            $query->where('stok', '>', 0)->whereColumn('stok', '<=', 'min_stok');
        } elseif ($this->statusFilter === 'habis') {
            $query->where('stok', '<=', 0);
        } elseif ($this->statusFilter === 'aman') {
            $query->whereColumn('stok', '>', 'min_stok');
        }

        $products = $query->orderBy('nama')->paginate(25);
        $golongans = Golongan::orderBy('kode_golongan')->get();

        return view('livewire.admin.laporan-stok', [
            'products' => $products,
            'golongans' => $golongans,
            'totalNilaiModal' => $totalNilaiModal,
            'totalNilaiRetail' => $totalNilaiRetail,
            'totalPotensiLaba' => $totalPotensiLaba,
            'totalMenipis' => $totalMenipis,
            'totalHabis' => $totalHabis,
        ]);
    }
}
