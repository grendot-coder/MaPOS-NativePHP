<?php

namespace App\Livewire\Kasir;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Dropshipper;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Services\MidtransService;
use App\Services\SaleService;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Kasir POS - MaPOS')]
class PosIndex extends Component
{
    // Shift Laci Kasir (Cash Drawer Settlement)
    public ?string $activeShiftId = null;

    public ?string $selectedShiftEmployeeId = null;

    public string $namaPetugasInput = '';

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

    // Member & Poin Loyalitas
    public ?string $selectedCustomerId = null;

    public string $customerSearch = '';

    public bool $usePoints = false;

    public int $poinDipakai = 0;

    public float $potonganPoin = 0;

    // Mode Order Dropshipper
    public bool $isDropship = false;

    public ?string $selectedDropshipperId = null;

    public string $dropshipPenerimaNama = '';

    public string $dropshipPenerimaTelepon = '';

    public string $dropshipPenerimaAlamat = '';

    public float $diskonDropship = 0;

    public string $receiptPrintMode = 'struk';

    // Modal Pembayaran
    public bool $showPaymentModal = false;

    public string $paymentMethod = 'cash';

    public $nominalBayar = 0;

    public float $kembalian = 0;

    // Midtrans QRIS Sandbox / Core API
    public ?string $midtransOrderId = null;

    public ?string $midtransQrUrl = null;

    public ?string $midtransQrString = null;

    public string $midtransStatus = '';

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

    public function openShiftModal(): void
    {
        $this->resetValidation();
        $linkedEmployee = Employee::where('user_id', auth()->id())
            ->where('status_aktif', true)
            ->first();

        $this->selectedShiftEmployeeId = $linkedEmployee?->id;
        $this->namaPetugasInput = $linkedEmployee?->nama ?? '';
        $this->catatanShift = '';
        $this->showOpenShiftModal = true;
    }

    public function updatedSelectedShiftEmployeeId(?string $value): void
    {
        if (blank($value)) {
            return;
        }

        $employee = Employee::find($value);
        if ($employee) {
            $this->namaPetugasInput = $employee->nama;
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

        $this->validate([
            'selectedShiftEmployeeId' => ['nullable', 'exists:employees,id'],
            'namaPetugasInput' => ['required', 'string', 'min:2', 'max:100'],
            'saldoAwalInput' => ['required', 'numeric', 'min:0'],
        ], [
            'namaPetugasInput.required' => 'Nama pegawai yang mengisi shift wajib diisi.',
            'namaPetugasInput.min' => 'Nama pegawai minimal 2 karakter.',
            'saldoAwalInput.required' => 'Modal awal laci wajib diisi.',
            'saldoAwalInput.min' => 'Modal awal laci tidak boleh negatif.',
        ]);

        $saldoAwal = max(0, (float) $this->saldoAwalInput);
        $namaPetugas = trim($this->namaPetugasInput);

        $shift = CashShift::create([
            'user_id' => auth()->id(),
            'employee_id' => filled($this->selectedShiftEmployeeId) ? $this->selectedShiftEmployeeId : null,
            'nama_petugas' => $namaPetugas,
            'waktu_buka' => now(),
            'saldo_awal' => $saldoAwal,
            'saldo_akhir_sistem' => $saldoAwal,
            'status' => 'open',
            'catatan' => filled($this->catatanShift) ? trim($this->catatanShift) : null,
        ]);

        $this->activeShiftId = $shift->id;
        $this->selectedShiftEmployeeId = null;
        $this->namaPetugasInput = '';
        $this->catatanShift = '';
        $this->showOpenShiftModal = false;

        try {
            Flux::toast(variant: 'success', text: "Shift [{$namaPetugas}] dibuka dengan modal Rp ".number_format($saldoAwal, 0, ',', '.'));
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

        $pricing = $product->resolveUnitPriceForQty($targetQty);
        $hargaSatuan = $pricing['harga'];
        $diskonPersen = $product->resolveEffectiveDiscount();
        $hargaSetelahDiskon = $hargaSatuan - ($hargaSatuan * ($diskonPersen / 100));

        $this->cart[$id] = [
            'product_id' => $product->id,
            'kode_produk' => $product->kode_produk,
            'nama' => $product->nama,
            'satuan' => $product->satuan,
            'harga_satuan' => $hargaSatuan,
            'tier_harga' => $pricing['tier'],
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
        if ($product) {
            $pricing = $product->resolveUnitPriceForQty($newQty);
            $hargaSatuan = $pricing['harga'];
            $tierHarga = $pricing['tier'];
            $diskonPersen = $product->resolveEffectiveDiscount();
        } else {
            $hargaSatuan = (float) $item['harga_satuan'];
            $tierHarga = $item['tier_harga'] ?? null;
            $diskonPersen = (float) $item['diskon'];
        }

        $hargaSetelahDiskon = $hargaSatuan - ($hargaSatuan * ($diskonPersen / 100));

        $this->cart[$productId]['harga_satuan'] = $hargaSatuan;
        $this->cart[$productId]['tier_harga'] = $tierHarga;
        $this->cart[$productId]['diskon'] = $diskonPersen;
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
        $this->resetMidtransState();
    }

    public function resetMidtransState(): void
    {
        $this->midtransOrderId = null;
        $this->midtransQrUrl = null;
        $this->midtransQrString = null;
        $this->midtransStatus = '';
    }

    public function generateMidtransQris(MidtransService $midtrans): void
    {
        if (empty($this->cart) || $this->grandTotal <= 0) {
            return;
        }

        $orderId = 'POS-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
        $result = $midtrans->createQrisCharge($orderId, (int) round($this->grandTotal));

        if (! $result['success']) {
            try {
                Flux::toast(variant: 'danger', text: $result['message']);
            } catch (\Throwable) {
            }

            return;
        }

        $this->midtransOrderId = $result['order_id'];
        $this->midtransQrUrl = $result['qr_url'];
        $this->midtransQrString = $result['qr_string'];
        $this->midtransStatus = $result['status'];
    }

    public function checkMidtransStatus(MidtransService $midtrans, SaleService $saleService): void
    {
        if (! $this->midtransOrderId || $this->midtransStatus !== 'pending') {
            return;
        }

        $result = $midtrans->checkStatus($this->midtransOrderId);
        if (! $result['success']) {
            return;
        }

        $this->midtransStatus = $result['status'];

        if ($result['is_paid']) {
            $this->paymentMethod = 'qris';
            $this->nominalBayar = $this->grandTotal;
            $this->kembalian = 0;
            $this->processTransaction($saleService);
        } elseif ($result['is_failed']) {
            try {
                Flux::toast(variant: 'warning', text: "QRIS {$this->midtransOrderId} dibatalkan / kedaluwarsa ({$result['status']}).");
            } catch (\Throwable) {
            }
        }
    }

    public function cancelMidtransQris(MidtransService $midtrans): void
    {
        if ($this->midtransOrderId) {
            $midtrans->cancelTransaction($this->midtransOrderId);
        }
        $this->resetMidtransState();
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->lastSaleId = null;
    }

    public function selectCustomer(string $customerId): void
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->selectedCustomerId = $customer->id;
            $this->customerSearch = '';
            $this->usePoints = false;
            $this->calculateTotals();
        }
    }

    public function clearCustomer(): void
    {
        $this->selectedCustomerId = null;
        $this->customerSearch = '';
        $this->usePoints = false;
        $this->poinDipakai = 0;
        $this->potonganPoin = 0;
        $this->calculateTotals();
    }

    public function updatedUsePoints(): void
    {
        $this->calculateTotals();
    }

    public function updatedIsDropship(bool $value): void
    {
        if (! $value) {
            $this->clearDropship();

            return;
        }

        $this->calculateTotals();
    }

    public function updatedSelectedDropshipperId(): void
    {
        $this->calculateTotals();
    }

    public function clearDropship(): void
    {
        $this->isDropship = false;
        $this->selectedDropshipperId = null;
        $this->dropshipPenerimaNama = '';
        $this->dropshipPenerimaTelepon = '';
        $this->dropshipPenerimaAlamat = '';
        $this->diskonDropship = 0;
        $this->calculateTotals();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->usePoints = false;
        $this->poinDipakai = 0;
        $this->potonganPoin = 0;
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
        $rawTotal = max(0, $subtotal - $totalDiskon);

        $this->diskonDropship = 0;
        if ($this->isDropship && $this->selectedDropshipperId && $rawTotal > 0) {
            $dropshipper = Dropshipper::find($this->selectedDropshipperId);
            if ($dropshipper && (float) $dropshipper->diskon_khusus > 0) {
                $this->diskonDropship = (float) round($rawTotal * ((float) $dropshipper->diskon_khusus / 100), 0);
                $rawTotal = max(0, $rawTotal - $this->diskonDropship);
            }
        }

        $this->poinDipakai = 0;
        $this->potonganPoin = 0;

        if ($this->usePoints && $this->selectedCustomerId && $rawTotal > 0) {
            $customer = Customer::find($this->selectedCustomerId);
            if ($customer && $customer->poin > 0) {
                $maxPotongan = $customer->poin * 100;
                $potongan = min($rawTotal, $maxPotongan);
                $this->poinDipakai = (int) floor($potongan / 100);
                $this->potonganPoin = (float) ($this->poinDipakai * 100);
            }
        }

        $this->grandTotal = max(0, $rawTotal - $this->potonganPoin);

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

        $this->paymentMethod = 'cash';
        $this->nominalBayar = $this->grandTotal;
        $this->updateKembalian();
        $this->showPaymentModal = true;
    }

    public function updatedPaymentMethod(string $value): void
    {
        if ($value !== 'qris') {
            $this->resetMidtransState();
        }

        if (in_array($value, ['qris', 'transfer'], true)) {
            $this->nominalBayar = $this->grandTotal;
            $this->kembalian = 0;
        } elseif ($value === 'tempo') {
            $this->nominalBayar = 0;
            $this->kembalian = 0;
        } else {
            $this->nominalBayar = $this->grandTotal;
            $this->updateKembalian();
        }
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
        if ($this->paymentMethod !== 'cash') {
            $this->kembalian = 0;

            return;
        }

        $bayar = (float) $this->nominalBayar;
        $this->kembalian = max(0, $bayar - $this->grandTotal);
    }

    public function processTransaction(SaleService $saleService): void
    {
        $bayar = (float) $this->nominalBayar;

        if ($this->paymentMethod === 'cash' && $bayar < $this->grandTotal) {
            try {
                Flux::toast(variant: 'danger', text: 'Uang pembayaran kurang dari total tagihan.');
            } catch (\Throwable) {
            }

            return;
        }

        if ($this->paymentMethod === 'tempo' && ! $this->selectedCustomerId) {
            try {
                Flux::toast(variant: 'danger', text: 'Pilih member pelanggan terlebih dahulu untuk pembayaran Bon / Tempo.');
            } catch (\Throwable) {
            }

            return;
        }

        if ($this->isDropship) {
            if (! $this->selectedDropshipperId) {
                try {
                    Flux::toast(variant: 'danger', text: 'Pilih mitra dropshipper pengirim terlebih dahulu.');
                } catch (\Throwable) {
                }

                return;
            }

            if (blank($this->dropshipPenerimaNama)) {
                try {
                    Flux::toast(variant: 'danger', text: 'Nama penerima paket dropship wajib diisi.');
                } catch (\Throwable) {
                }

                return;
            }
        }

        $dropshipData = $this->isDropship ? [
            'is_dropship' => true,
            'dropshipper_id' => $this->selectedDropshipperId,
            'penerima_nama' => trim($this->dropshipPenerimaNama),
            'penerima_telepon' => filled($this->dropshipPenerimaTelepon) ? trim($this->dropshipPenerimaTelepon) : null,
            'penerima_alamat' => filled($this->dropshipPenerimaAlamat) ? trim($this->dropshipPenerimaAlamat) : null,
        ] : [];

        try {
            $sale = $saleService->processSale(
                auth()->user(),
                $this->cart,
                $this->grandTotal,
                $bayar,
                $this->kembalian,
                $this->activeShiftId,
                $this->paymentMethod,
                $this->selectedCustomerId,
                $this->poinDipakai,
                $this->potonganPoin,
                $dropshipData
            );

            $this->lastSaleId = $sale->id;
            $this->receiptPrintMode = 'struk';
            $this->clearCart();
            $this->clearCustomer();
            $this->clearDropship();
            $this->resetMidtransState();
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
            $lastSale = Sale::with(['items', 'kasir', 'cashShift', 'customer', 'dropshipper'])->find($this->lastSaleId);
        }

        $selectedCustomer = null;
        if ($this->selectedCustomerId) {
            $selectedCustomer = Customer::find($this->selectedCustomerId);
        }

        $customerResults = collect();
        if (strlen(trim($this->customerSearch)) >= 2) {
            $cq = trim($this->customerSearch);
            $customerResults = Customer::where('nama', 'like', "%{$cq}%")
                ->orWhere('kode_member', 'like', "%{$cq}%")
                ->orWhere('telepon', 'like', "%{$cq}%")
                ->limit(6)
                ->get();
        }

        $activeShift = null;
        if ($this->activeShiftId) {
            $activeShift = CashShift::with(['user', 'sales', 'pengeluarans'])->find($this->activeShiftId);
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

        $activeEmployees = Employee::with('jabatan')
            ->where('status_aktif', true)
            ->orderBy('nama')
            ->get();

        $activeDropshippers = Dropshipper::where('status_aktif', true)
            ->orderBy('nama')
            ->get();

        return view('livewire.kasir.pos-index', [
            'lastSale' => $lastSale,
            'selectedCustomer' => $selectedCustomer,
            'customerResults' => $customerResults,
            'midtransConfigured' => app(MidtransService::class)->isConfigured(),
            'activeEmployees' => $activeEmployees,
            'activeDropshippers' => $activeDropshippers,
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
