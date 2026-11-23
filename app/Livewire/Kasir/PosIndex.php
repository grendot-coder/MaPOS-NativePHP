<?php

namespace App\Livewire\Kasir;

use App\Models\CashShift;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Kasir POS - MaPOS')]
class PosIndex extends Component
{
    // Shift Laci Kasir (Cash Drawer Settlement)
    public ?string $activeShiftId = null;

    public $saldoAwalInput = 100000;

    public bool $showOpenShiftModal = false;

    public bool $showCloseShiftModal = false;

    public $saldoFisikInput = 0;

    public string $catatanShift = '';

    public bool $showShiftReceiptModal = false;

    public ?string $lastClosedShiftId = null;

    // Input Barcode Scanner (Autofocus)
    public string $barcodeInput = '';

    /**
     * Keranjang belanja kasir:
     * [product_id => ['product_id', 'kode_produk', 'nama', 'satuan', 'harga_satuan', 'diskon', 'qty', 'subtotal', 'stok_max']]
     *
     * @var array<string, array{product_id: string, kode_produk: string, nama: string, satuan: string, harga_satuan: float, diskon: float, qty: int, subtotal: float, stok_max: int}>
     */
    public array $cart = [];

    public float $subtotal = 0;

    public float $totalDiskon = 0;

    public float $grandTotal = 0;

    // Modal Pembayaran
    public bool $showPaymentModal = false;

    public string $paymentMethod = 'cash';

    public $nominalBayar = 0;

    public float $kembalian = 0;

    // Modal Struk / Cetak
    public bool $showReceiptModal = false;

    public ?string $lastSaleId = null;

    // Modal Pencarian Manual Barang
    public bool $showSearchModal = false;

    public string $searchKeyword = '';

    public function mount(): void
    {
        $this->calculateTotals();
        $this->loadActiveShift();
    }

    public function loadActiveShift(): void
    {
        $shift = CashShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->latest('waktu_buka')
            ->first();

        if ($shift) {
            $this->activeShiftId = $shift->id;
        }
    }

    public function openShift(): void
    {
        $existingShift = CashShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($existingShift) {
            $this->activeShiftId = $existingShift->id;
            $this->showOpenShiftModal = false;

            try {
                Flux::toast(variant: 'warning', text: 'Anda sudah memiliki shift kasir yang sedang aktif.');
            } catch (\Throwable) {
            }

            return;
        }

        $saldoAwal = max(0, (float) $this->saldoAwalInput);

        $shift = CashShift::create([
            'user_id' => auth()->id(),
            'waktu_buka' => now(),
            'saldo_awal' => $saldoAwal,
            'saldo_akhir_sistem' => $saldoAwal,
            'status' => 'open',
        ]);

        $this->activeShiftId = $shift->id;
        $this->showOpenShiftModal = false;

        try {
            Flux::toast(variant: 'success', text: 'Shift kasir berhasil dibuka dengan modal Rp '.number_format($saldoAwal, 0, ',', '.'));
        } catch (\Throwable) {
        }
    }

    public function setSaldoAwalPreset(float $amount): void
    {
        $this->saldoAwalInput = $amount;
    }

    public function confirmCloseShift(): void
    {
        if (! $this->activeShiftId) {
            return;
        }

        $shift = CashShift::with(['sales', 'pengeluarans'])->find($this->activeShiftId);
        if ($shift) {
            $this->saldoFisikInput = $shift->saldo_saat_ini;
            $this->catatanShift = '';
            $this->showCloseShiftModal = true;
        }
    }

    public function closeShift(): void
    {
        if (! $this->activeShiftId) {
            return;
        }

        $shift = CashShift::with(['sales', 'pengeluarans'])->findOrFail($this->activeShiftId);

        if ($shift->status !== 'open') {
            $this->activeShiftId = null;
            $this->showCloseShiftModal = false;

            return;
        }

        $saldoSistem = (float) $shift->saldo_saat_ini;
        $saldoFisik = max(0, (float) $this->saldoFisikInput);
        $selisih = $saldoFisik - $saldoSistem;

        $shift->update([
            'waktu_tutup' => now(),
            'saldo_akhir_sistem' => $saldoSistem,
            'saldo_akhir_fisik' => $saldoFisik,
            'selisih' => $selisih,
            'status' => 'closed',
            'catatan' => $this->catatanShift,
        ]);

        $this->lastClosedShiftId = $shift->id;
        $this->activeShiftId = null;
        $this->showCloseShiftModal = false;
        $this->showShiftReceiptModal = true;

        try {
            Flux::toast(variant: 'success', text: 'Shift kasir berhasil ditutup dan direkap!');
        } catch (\Throwable) {
        }
    }

    public function closeShiftReceiptModal(): void
    {
        $this->showShiftReceiptModal = false;
        $this->lastClosedShiftId = null;
    }

    public function scanBarcode(SaleService $saleService): void
    {
        $barcode = trim($this->barcodeInput);
        if (empty($barcode)) {
            return;
        }

        $product = $saleService->findProductByBarcode($barcode);

        if (! $product) {
            try {
                Flux::toast(
                    variant: 'danger',
                    text: "Produk dengan barcode [{$barcode}] tidak terdaftar!"
                );
            } catch (\Throwable) {
            }
            $this->barcodeInput = '';

            return;
        }

        $this->addProductToCart($product);
        $this->barcodeInput = '';
    }

    public function addProductToCart(Product $product, int $addQty = 1): void
    {
        $id = $product->id;
        $currentQty = isset($this->cart[$id]) ? $this->cart[$id]['qty'] : 0;
        $targetQty = $currentQty + $addQty;

        if ($targetQty > $product->stok) {
            try {
                Flux::toast(
                    variant: 'danger',
                    text: "Stok tidak mencukupi untuk [{$product->nama}]. Stok tersedia: {$product->stok} {$product->satuan}."
                );
            } catch (\Throwable) {
            }

            return;
        }

        $hargaRetail = (float) $product->harga_retail;
        $diskonPersen = (float) $product->diskon;
        $hargaSetelahDiskon = $hargaRetail - ($hargaRetail * ($diskonPersen / 100));

        $this->cart[$id] = [
            'product_id' => $product->id,
            'kode_produk' => $product->kode_produk,
            'nama' => $product->nama,
            'satuan' => $product->satuan,
            'harga_satuan' => $hargaRetail,
            'diskon' => $diskonPersen,
            'qty' => $targetQty,
            'subtotal' => $hargaSetelahDiskon * $targetQty,
            'stok_max' => $product->stok,
        ];

        $this->calculateTotals();
    }

    public function updateQty(string $productId, int $newQty): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($newQty <= 0) {
            $this->removeItem($productId);

            return;
        }

        $product = Product::find($productId);
        if ($product && $newQty > $product->stok) {
            try {
                Flux::toast(
                    variant: 'danger',
                    text: "Stok tidak mencukupi untuk [{$product->nama}]. Maksimal: {$product->stok} {$product->satuan}."
                );
            } catch (\Throwable) {
            }
            $newQty = $product->stok;
        }

        $item = $this->cart[$productId];
        $hargaSetelahDiskon = $item['harga_satuan'] - ($item['harga_satuan'] * ($item['diskon'] / 100));

        $this->cart[$productId]['qty'] = $newQty;
        $this->cart[$productId]['subtotal'] = $hargaSetelahDiskon * $newQty;

        $this->calculateTotals();
    }

    public function removeItem(string $productId): void
    {
        unset($this->cart[$productId]);
        $this->calculateTotals();
    }

    public function selectProduct(string $productId): void
    {
        $product = Product::find($productId);
        if ($product) {
            $this->addProductToCart($product);
            $this->showSearchModal = false;
            $this->searchKeyword = '';
        }
    }

    public function incrementQty(string $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->updateQty($productId, $this->cart[$productId]['qty'] + 1);
        }
    }

    public function decrementQty(string $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->updateQty($productId, $this->cart[$productId]['qty'] - 1);
        }
    }

    public function openSearchModal(): void
    {
        $this->searchKeyword = '';
        $this->showSearchModal = true;
    }

    public function closeSearchModal(): void
    {
        $this->showSearchModal = false;
        $this->searchKeyword = '';
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->lastSaleId = null;
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $subtotal = 0;
        $totalDiskon = 0;

        foreach ($this->cart as $item) {
            $kotor = $item['harga_satuan'] * $item['qty'];
            $diskonItem = $kotor * ($item['diskon'] / 100);

            $subtotal += $kotor;
            $totalDiskon += $diskonItem;
        }

        $this->subtotal = $subtotal;
        $this->totalDiskon = $totalDiskon;
        $this->grandTotal = max(0, $subtotal - $totalDiskon);

        $this->updateKembalian();
    }

    public function openPayment(): void
    {
        if (empty($this->cart)) {
            try {
                Flux::toast(variant: 'warning', text: 'Keranjang belanja masih kosong.');
            } catch (\Throwable) {
            }

            return;
        }

        $this->nominalBayar = $this->grandTotal;
        $this->updateKembalian();
        $this->showPaymentModal = true;
    }

    public function setNominalBayar(float $amount): void
    {
        $this->nominalBayar = $amount;
        $this->updateKembalian();
    }

    public function updatedNominalBayar(): void
    {
        $this->updateKembalian();
    }

    public function updateKembalian(): void
    {
        $bayar = (float) $this->nominalBayar;
        $this->kembalian = max(0, $bayar - $this->grandTotal);
    }

    public function processTransaction(SaleService $saleService): void
    {
        $bayar = (float) $this->nominalBayar;

        if ($bayar < $this->grandTotal) {
            try {
                Flux::toast(variant: 'danger', text: 'Uang pembayaran kurang dari total tagihan.');
            } catch (\Throwable) {
            }

            return;
        }

        try {
            $sale = $saleService->processSale(
                auth()->user(),
                $this->cart,
                $this->grandTotal,
                $bayar,
                $this->kembalian,
                $this->activeShiftId
            );

            $this->lastSaleId = $sale->id;
            $this->clearCart();
            $this->showPaymentModal = false;
            $this->showReceiptModal = true;

            Flux::toast(variant: 'success', text: "Transaksi {$sale->no_transaksi} berhasil disimpan!");
        } catch (\Throwable $e) {
            try {
                Flux::toast(variant: 'danger', text: 'Gagal memproses transaksi: '.$e->getMessage());
            } catch (\Throwable) {
            }
        }
    }

    public function render(SaleService $saleService)
    {
        $lastSale = null;
        if ($this->lastSaleId) {
            $lastSale = Sale::with(['items', 'kasir'])->find($this->lastSaleId);
        }

        $activeShift = null;
        if ($this->activeShiftId) {
            $activeShift = CashShift::with(['sales', 'pengeluarans'])->find($this->activeShiftId);
        }

        $lastClosedShift = null;
        if ($this->lastClosedShiftId) {
            $lastClosedShift = CashShift::with(['user', 'sales', 'pengeluarans'])->find($this->lastClosedShiftId);
        }

        $saldoAwal = $activeShift ? $activeShift->saldo_awal : 0;
        $totalPenjualanShift = $activeShift ? $activeShift->total_penjualan : 0;
        $totalPengeluaranShift = $activeShift ? $activeShift->total_pengeluaran : 0;
        $saldoKasSaatIni = $activeShift ? $activeShift->saldo_saat_ini : 0;

        $searchResults = [];
        if ($this->showSearchModal && ! empty($this->searchKeyword)) {
            $searchResults = Product::where('nama', 'like', "%{$this->searchKeyword}%")
                ->orWhere('kode_produk', 'like', "%{$this->searchKeyword}%")
                ->limit(10)
                ->get();
        }

        // Quick Products (8 produk populer untuk klik cepat kasir)
        $quickProducts = Product::where('stok', '>', 0)
            ->orderBy('nama')
            ->limit(8)
            ->get();

        $nextTransactionNumber = $saleService->generateTransactionNumber();

        return view('livewire.kasir.pos-index', [
            'lastSale' => $lastSale,
            'activeShift' => $activeShift,
            'saldoAwal' => $saldoAwal,
            'totalPenjualanShift' => $totalPenjualanShift,
            'totalPengeluaranShift' => $totalPengeluaranShift,
            'saldoKasSaatIni' => $saldoKasSaatIni,
            'lastClosedShift' => $lastClosedShift,
            'searchResults' => $searchResults,
            'quickProducts' => $quickProducts,
            'nextTransactionNumber' => $nextTransactionNumber,
        ]);
    }
}
