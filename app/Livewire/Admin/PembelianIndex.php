<?php

namespace App\Livewire\Admin;

use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pembelian & Restock Barang - MaPOS')]
class PembelianIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterBulan = '';
    public string $statusFilter = '';

    // Modal Detail Faktur
    public bool $showDetailModal = false;
    public ?string $selectedPurchaseId = null;

    // Modal Tambah Supplier
    public bool $showSupplierModal = false;
    public string $supplierNama = '';
    public string $supplierTelepon = '';
    public string $supplierAlamat = '';
    public string $supplierKontak = '';

    public function mount(): void
    {
        $this->filterBulan = date('Y-m');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBulan(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function showDetail(string $purchaseId): void
    {
        $this->selectedPurchaseId = $purchaseId;
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedPurchaseId = null;
    }

    public function openSupplierModal(): void
    {
        $this->resetValidation();
        $this->supplierNama = '';
        $this->supplierTelepon = '';
        $this->supplierAlamat = '';
        $this->supplierKontak = '';
        $this->showSupplierModal = true;
    }

    public function saveSupplier(): void
    {
        $this->validate([
            'supplierNama' => ['required', 'string', 'max:100'],
            'supplierTelepon' => ['nullable', 'string', 'max:50'],
            'supplierAlamat' => ['nullable', 'string', 'max:500'],
            'supplierKontak' => ['nullable', 'string', 'max:100'],
        ], [
            'supplierNama.required' => 'Nama supplier wajib diisi.',
        ]);

        Supplier::create([
            'nama' => trim($this->supplierNama),
            'telepon' => trim($this->supplierTelepon),
            'alamat' => trim($this->supplierAlamat),
            'nama_kontak' => trim($this->supplierKontak),
        ]);

        $this->showSupplierModal = false;

        try {
            Flux::toast(variant: 'success', text: 'Supplier baru berhasil ditambahkan.');
        } catch (\Throwable) {}
    }

    public function render()
    {
        $query = Purchase::with(['supplier', 'user', 'items.product'])
            ->latest('tanggal')
            ->latest('created_at');

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('no_faktur', 'like', "%{$this->search}%")
                    ->orWhere('nama_supplier', 'like', "%{$this->search}%");
            });
        }

        if (! empty($this->filterBulan)) {
            $start = Carbon::parse($this->filterBulan . '-01')->startOfMonth();
            $end = Carbon::parse($this->filterBulan . '-01')->endOfMonth();
            $query->whereBetween('tanggal', [$start, $end]);
        }

        if (! empty($this->statusFilter)) {
            $query->where('status_pembayaran', $this->statusFilter);
        }

        $purchases = $query->paginate(15);

        // KPI Ringkasan
        $kpiQuery = Purchase::query();
        if (! empty($this->filterBulan)) {
            $start = Carbon::parse($this->filterBulan . '-01')->startOfMonth();
            $end = Carbon::parse($this->filterBulan . '-01')->endOfMonth();
            $kpiQuery->whereBetween('tanggal', [$start, $end]);
        }
        $totalFaktur = $kpiQuery->count();
        $totalBelanja = (float) $kpiQuery->sum('total');
        $totalHutang = (float) (clone $kpiQuery)->where('status_pembayaran', 'hutang')->sum('total');

        $selectedPurchase = null;
        if ($this->selectedPurchaseId) {
            $selectedPurchase = Purchase::with(['supplier', 'user', 'items.product'])->find($this->selectedPurchaseId);
        }

        return view('livewire.admin.pembelian-index', [
            'purchases' => $purchases,
            'totalFaktur' => $totalFaktur,
            'totalBelanja' => $totalBelanja,
            'totalHutang' => $totalHutang,
            'selectedPurchase' => $selectedPurchase,
        ]);
    }
}
