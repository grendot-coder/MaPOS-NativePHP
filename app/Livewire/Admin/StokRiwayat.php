<?php

namespace App\Livewire\Admin;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Buku Kartu Stok - MaPOS')]
class StokRiwayat extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedProduct = '';
    public string $selectedJenis = '';
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void
    {
        // Default tanggal: 30 hari terakhir
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedProduct(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedJenis(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->selectedProduct = '';
        $this->selectedJenis = '';
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $query = StockMovement::with(['product.golongan']);

        // Filter nama/barcode produk/keterangan
        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('keterangan', 'like', "%{$this->search}%")
                    ->orWhereHas('product', function ($p) {
                        $p->where('nama', 'like', "%{$this->search}%")
                            ->orWhere('kode_produk', 'like', "%{$this->search}%");
                    });
            });
        }

        // Filter produk tertentu
        if (! empty($this->selectedProduct)) {
            $query->where('product_id', $this->selectedProduct);
        }

        // Filter jenis mutasi
        if (! empty($this->selectedJenis)) {
            $query->where('jenis', $this->selectedJenis);
        }

        // Filter rentang tanggal
        if (! empty($this->startDate)) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if (! empty($this->endDate)) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        // Hitung total statistik mutasi dari query yang difilter
        $statsQuery = clone $query;
        $totalMasuk = (clone $statsQuery)->where('jumlah', '>', 0)->sum('jumlah');
        $totalKeluar = (clone $statsQuery)->where('jumlah', '<', 0)->sum('jumlah');
        $totalRecords = (clone $statsQuery)->count();

        $movements = $query->latest('created_at')->paginate(20);

        // List semua produk untuk dropdown filter
        $productList = Product::orderBy('nama')->select('id', 'nama', 'kode_produk')->get();

        return view('livewire.admin.stok-riwayat', [
            'movements' => $movements,
            'productList' => $productList,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => abs($totalKeluar),
            'totalRecords' => $totalRecords,
            'jenisList' => StockMovementType::cases(),
        ]);
    }
}
