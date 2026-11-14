<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Laporan Penjualan - MaPOS')]
class LaporanPenjualan extends Component
{
    use WithPagination;

    public string $period = 'today';
    public string $startDate = '';
    public string $endDate = '';
    public string $selectedKasir = '';

    // Modal Detail & Cetak Ulang Struk
    public bool $showReceiptModal = false;
    public ?string $selectedSaleId = null;

    public function mount(): void
    {
        $this->setPeriod('today');
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;

        switch ($period) {
            case 'today':
                $this->startDate = Carbon::today()->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = Carbon::yesterday()->format('Y-m-d');
                $this->endDate = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            default:
                break;
        }

        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->period = 'custom';
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->period = 'custom';
        $this->resetPage();
    }

    public function updatingSelectedKasir(): void
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
        $query = Sale::with(['kasir', 'items.product']);

        if (! empty($this->startDate)) {
            $query->whereDate('waktu', '>=', $this->startDate);
        }
        if (! empty($this->endDate)) {
            $query->whereDate('waktu', '<=', $this->endDate);
        }
        if (! empty($this->selectedKasir)) {
            $query->where('kasir_id', $this->selectedKasir);
        }

        // Statistik
        $statsQuery = clone $query;
        $totalOmzet = (clone $statsQuery)->sum('total');
        $totalTransaksi = (clone $statsQuery)->count();
        $totalItemTerjual = (clone $statsQuery)
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->sum('sale_items.qty');
        $rataRataBelanja = $totalTransaksi > 0 ? $totalOmzet / $totalTransaksi : 0;

        $sales = $query->latest('waktu')->paginate(15);

        $selectedSale = null;
        if ($this->selectedSaleId) {
            $selectedSale = Sale::with(['items', 'kasir'])->find($this->selectedSaleId);
        }

        // Daftar kasir untuk filter
        $kasirList = User::whereIn('role', [UserRole::Kasir, UserRole::Admin])->orderBy('name')->get();

        return view('livewire.admin.laporan-penjualan', [
            'sales' => $sales,
            'selectedSale' => $selectedSale,
            'kasirList' => $kasirList,
            'totalOmzet' => $totalOmzet,
            'totalTransaksi' => $totalTransaksi,
            'totalItemTerjual' => $totalItemTerjual,
            'rataRataBelanja' => $rataRataBelanja,
        ]);
    }
}
