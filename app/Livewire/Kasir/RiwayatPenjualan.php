<?php

namespace App\Livewire\Kasir;

use App\Models\Sale;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Riwayat Transaksi Kasir - MaPOS')]
class RiwayatPenjualan extends Component
{
    use WithPagination;

    public string $filterDate = '';
    public bool $showReceiptModal = false;
    public ?string $selectedSaleId = null;

    public function mount(): void
    {
        $this->filterDate = Carbon::today()->format('Y-m-d');
    }

    public function updatingFilterDate(): void
    {
        $this->resetPage();
    }

    public function showReceipt(string $saleId): void
    {
        $this->selectedSaleId = $saleId;
        $this->showReceiptModal = true;
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->selectedSaleId = null;
    }

    public function render()
    {
        $user = auth()->user();

        $query = Sale::with(['items.product', 'kasir'])
            ->where('kasir_id', $user->id);

        if (! empty($this->filterDate)) {
            $query->whereDate('waktu', $this->filterDate);
        }

        // Statistik shift kasir
        $statsQuery = clone $query;
        $totalOmzet = (clone $statsQuery)->sum('total');
        $totalTransaksi = (clone $statsQuery)->count();

        $sales = $query->latest('waktu')->paginate(15);

        $selectedSale = null;
        if ($this->selectedSaleId) {
            $selectedSale = Sale::with(['items', 'kasir'])->find($this->selectedSaleId);
        }

        return view('livewire.kasir.riwayat-penjualan', [
            'sales' => $sales,
            'selectedSale' => $selectedSale,
            'totalOmzet' => $totalOmzet,
            'totalTransaksi' => $totalTransaksi,
        ]);
    }
}
