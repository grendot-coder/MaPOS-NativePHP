<?php

namespace App\Livewire\Admin;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    // Modal Bayar Cicilan Hutang Supplier
    public bool $showDebtModal = false;

    public ?string $payingPurchaseId = null;

    public $debtPaymentAmount = 0;

    public string $debtPaymentMethod = 'cash';

    public string $debtPaymentNote = '';

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

    public function markAsPaid(string $purchaseId): void
    {
        DB::transaction(function () use ($purchaseId): void {
            /** @var Purchase $purchase */
            $purchase = Purchase::lockForUpdate()->findOrFail($purchaseId);
            if ($purchase->status_pembayaran === 'lunas') {
                return;
            }

            $sisa = $purchase->sisa_hutang;
            if ($sisa > 0) {
                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'user_id' => Auth::id(),
                    'jumlah' => $sisa,
                    'metode' => 'cash',
                    'catatan' => 'Pelunasan penuh faktur',
                    'waktu' => now(),
                ]);
            }

            $purchase->update([
                'dibayar' => (float) $purchase->total,
                'status_pembayaran' => 'lunas',
            ]);

            try {
                Flux::toast(variant: 'success', text: "Faktur #{$purchase->no_faktur} berhasil ditandai LUNAS.");
            } catch (\Throwable) {
            }
        });
    }

    public function openDebtModal(string $purchaseId): void
    {
        $this->resetValidation();
        $purchase = Purchase::findOrFail($purchaseId);
        $this->payingPurchaseId = $purchase->id;
        $this->debtPaymentAmount = $purchase->sisa_hutang;
        $this->debtPaymentMethod = 'cash';
        $this->debtPaymentNote = '';
        $this->showDebtModal = true;
    }

    public function saveDebtPayment(): void
    {
        $this->validate([
            'debtPaymentAmount' => ['required', 'numeric', 'min:100'],
            'debtPaymentMethod' => ['required', 'in:cash,transfer'],
        ], [
            'debtPaymentAmount.min' => 'Nominal pembayaran minimal Rp 100.',
        ]);

        try {
            DB::transaction(function (): void {
                /** @var Purchase $purchase */
                $purchase = Purchase::lockForUpdate()->findOrFail($this->payingPurchaseId);
                $sisa = $purchase->sisa_hutang;
                if ($sisa <= 0) {
                    throw new \InvalidArgumentException('Faktur ini sudah lunas.');
                }

                $bayar = min($sisa, (float) $this->debtPaymentAmount);

                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'user_id' => Auth::id(),
                    'jumlah' => $bayar,
                    'metode' => $this->debtPaymentMethod,
                    'catatan' => filled($this->debtPaymentNote) ? trim($this->debtPaymentNote) : null,
                    'waktu' => now(),
                ]);

                $dibayarBaru = (float) $purchase->dibayar + $bayar;
                $sisaBaru = max(0.0, (float) $purchase->total - $dibayarBaru);

                $purchase->update([
                    'dibayar' => $dibayarBaru,
                    'status_pembayaran' => $sisaBaru <= 0 ? 'lunas' : 'hutang',
                ]);
            });

            $this->showDebtModal = false;

            try {
                Flux::toast(variant: 'success', text: 'Pembayaran hutang supplier berhasil dicatat.');
            } catch (\Throwable) {
            }
        } catch (\Throwable $e) {
            $this->addError('debtPaymentAmount', $e->getMessage());
        }
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
        } catch (\Throwable) {
        }
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
            $start = Carbon::parse($this->filterBulan.'-01')->startOfMonth();
            $end = Carbon::parse($this->filterBulan.'-01')->endOfMonth();
            $query->whereBetween('tanggal', [$start, $end]);
        }

        if (! empty($this->statusFilter)) {
            $query->where('status_pembayaran', $this->statusFilter);
        }

        $purchases = $query->paginate(15);

        // KPI Ringkasan
        $kpiQuery = Purchase::query();
        if (! empty($this->filterBulan)) {
            $start = Carbon::parse($this->filterBulan.'-01')->startOfMonth();
            $end = Carbon::parse($this->filterBulan.'-01')->endOfMonth();
            $kpiQuery->whereBetween('tanggal', [$start, $end]);
        }
        $totalFaktur = $kpiQuery->count();
        $totalBelanja = (float) $kpiQuery->sum('total');
        $totalHutang = (float) (clone $kpiQuery)
            ->where('status_pembayaran', 'hutang')
            ->selectRaw('COALESCE(SUM(total - dibayar), 0) as sisa')
            ->value('sisa');

        $selectedPurchase = null;
        if ($this->selectedPurchaseId) {
            $selectedPurchase = Purchase::with(['supplier', 'user', 'items.product', 'payments.user'])->find($this->selectedPurchaseId);
        }

        $payingPurchase = null;
        if ($this->showDebtModal && $this->payingPurchaseId) {
            $payingPurchase = Purchase::find($this->payingPurchaseId);
        }

        return view('livewire.admin.pembelian-index', [
            'purchases' => $purchases,
            'totalFaktur' => $totalFaktur,
            'totalBelanja' => $totalBelanja,
            'totalHutang' => $totalHutang,
            'selectedPurchase' => $selectedPurchase,
            'payingPurchase' => $payingPurchase,
        ]);
    }
}
