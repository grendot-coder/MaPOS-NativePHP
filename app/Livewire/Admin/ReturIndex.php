<?php

namespace App\Livewire\Admin;

use App\Models\CashShift;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\ReturnService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Retur Barang (Penjualan & Pembelian) - MaPOS')]
class ReturIndex extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'penjualan';

    // Retur Penjualan State
    public string $saleSearch = '';

    public ?string $selectedSaleId = null;

    /** @var array<string, int> */
    public array $saleReturnQtys = [];

    public string $saleRefundMethod = 'cash';

    public string $saleReturnReason = '';

    // Retur Pembelian State
    public string $purchaseSearch = '';

    public ?string $selectedPurchaseId = null;

    /** @var array<string, int> */
    public array $purchaseReturnQtys = [];

    public string $purchaseRefundMethod = 'potong_hutang';

    public string $purchaseReturnReason = '';

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['penjualan', 'pembelian'], true) ? $tab : 'penjualan';
        $this->resetValidation();
    }

    public function selectSale(string $saleId): void
    {
        $sale = Sale::with('items')->find($saleId);
        if (! $sale) {
            return;
        }

        $this->selectedSaleId = $sale->id;
        $this->saleReturnQtys = [];
        foreach ($sale->items as $item) {
            $this->saleReturnQtys[$item->id] = 0;
        }
        $this->saleRefundMethod = $sale->status_pembayaran === 'piutang' ? 'potong_piutang' : 'cash';
        $this->saleReturnReason = '';
    }

    public function submitSaleReturn(ReturnService $service): void
    {
        if (! $this->selectedSaleId) {
            return;
        }

        try {
            $activeShift = CashShift::activeShift(Auth::id())->first();

            $saleReturn = $service->processSaleReturn(
                Auth::user(),
                $this->selectedSaleId,
                $this->saleReturnQtys,
                $this->saleRefundMethod,
                $this->saleReturnReason,
                $activeShift?->id
            );

            $this->selectedSaleId = null;
            $this->saleReturnQtys = [];
            $this->saleReturnReason = '';

            try {
                Flux::toast(variant: 'success', text: "Retur penjualan {$saleReturn->no_retur} berhasil disimpan & stok dikembalikan.");
            } catch (\Throwable) {
            }
        } catch (\Throwable $e) {
            $this->addError('saleReturn', $e->getMessage());
        }
    }

    public function selectPurchase(string $purchaseId): void
    {
        $purchase = Purchase::with('items')->find($purchaseId);
        if (! $purchase) {
            return;
        }

        $this->selectedPurchaseId = $purchase->id;
        $this->purchaseReturnQtys = [];
        foreach ($purchase->items as $item) {
            $this->purchaseReturnQtys[$item->id] = 0;
        }
        $this->purchaseRefundMethod = $purchase->status_pembayaran === 'hutang' ? 'potong_hutang' : 'refund_tunai';
        $this->purchaseReturnReason = '';
    }

    public function submitPurchaseReturn(ReturnService $service): void
    {
        if (! $this->selectedPurchaseId) {
            return;
        }

        try {
            $purchaseReturn = $service->processPurchaseReturn(
                Auth::user(),
                $this->selectedPurchaseId,
                $this->purchaseReturnQtys,
                $this->purchaseRefundMethod,
                $this->purchaseReturnReason
            );

            $this->selectedPurchaseId = null;
            $this->purchaseReturnQtys = [];
            $this->purchaseReturnReason = '';

            try {
                Flux::toast(variant: 'success', text: "Retur pembelian {$purchaseReturn->no_retur} berhasil disimpan & stok dipotong.");
            } catch (\Throwable) {
            }
        } catch (\Throwable $e) {
            $this->addError('purchaseReturn', $e->getMessage());
        }
    }

    public function render()
    {
        $saleResults = Sale::query()
            ->when($this->saleSearch, fn ($q, $s) => $q->where('no_transaksi', 'like', "%{$s}%"))
            ->latest('waktu')
            ->limit(10)
            ->get();

        $selectedSale = null;
        $saleReturnedMap = [];
        if ($this->selectedSaleId) {
            $selectedSale = Sale::with(['items', 'customer', 'kasir'])->find($this->selectedSaleId);
            if ($selectedSale) {
                $saleReturnedMap = SaleReturnItem::whereIn('sale_item_id', $selectedSale->items->pluck('id'))
                    ->selectRaw('sale_item_id, SUM(qty) as returned_qty')
                    ->groupBy('sale_item_id')
                    ->pluck('returned_qty', 'sale_item_id')
                    ->toArray();
            }
        }

        $purchaseResults = Purchase::query()
            ->when($this->purchaseSearch, fn ($q, $s) => $q->where('no_faktur', 'like', "%{$s}%")->orWhere('nama_supplier', 'like', "%{$s}%"))
            ->latest('tanggal')
            ->limit(10)
            ->get();

        $selectedPurchase = null;
        $purchaseReturnedMap = [];
        if ($this->selectedPurchaseId) {
            $selectedPurchase = Purchase::with(['items.product', 'supplier'])->find($this->selectedPurchaseId);
            if ($selectedPurchase) {
                $purchaseReturnedMap = PurchaseReturnItem::whereIn('purchase_item_id', $selectedPurchase->items->pluck('id'))
                    ->selectRaw('purchase_item_id, SUM(qty) as returned_qty')
                    ->groupBy('purchase_item_id')
                    ->pluck('returned_qty', 'purchase_item_id')
                    ->toArray();
            }
        }

        $recentSaleReturns = SaleReturn::with(['sale', 'user', 'items'])->latest('waktu')->limit(10)->get();
        $recentPurchaseReturns = PurchaseReturn::with(['purchase', 'user', 'items'])->latest('waktu')->limit(10)->get();

        return view('livewire.admin.retur-index', [
            'saleResults' => $saleResults,
            'selectedSale' => $selectedSale,
            'saleReturnedMap' => $saleReturnedMap,
            'purchaseResults' => $purchaseResults,
            'selectedPurchase' => $selectedPurchase,
            'purchaseReturnedMap' => $purchaseReturnedMap,
            'recentSaleReturns' => $recentSaleReturns,
            'recentPurchaseReturns' => $recentPurchaseReturns,
        ]);
    }
}
