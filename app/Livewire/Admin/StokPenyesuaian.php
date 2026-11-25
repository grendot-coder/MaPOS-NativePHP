<?php

namespace App\Livewire\Admin;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Penyesuaian Stok (Stock Opname) - MaPOS')]
class StokPenyesuaian extends Component
{
    // Input Barcode Scanner (Autofocus)
    public string $barcodeInput = '';

    // Produk yang dipilih untuk disesuaikan
    public ?string $selectedProductId = null;

    // Form nilai penyesuaian
    public $stok_etalase_baru = 0;

    public $stok_gudang_baru = 0;

    public string $keterangan = 'Stock Opname Fisik';

    // Preset alasan cepat
    public array $presetKeterangan = [
        'Stock Opname Fisik',
        'Barang Rusak / Pecah',
        'Barang Kedaluwarsa (Expired)',
        'Selisih Hitung Kasir',
        'Retur dari Pelanggan',
        'Koreksi Input Awal',
    ];

    public function mount(?string $productId = null): void
    {
        $targetProductId = $productId ?? request()->query('productId');

        if ($targetProductId) {
            $this->selectProduct($targetProductId);
        }
    }

    public function scanBarcode(): void
    {
        $code = trim($this->barcodeInput);
        if (empty($code)) {
            return;
        }

        // Cari exact match atau fallback UPC-A
        $product = Product::where('kode_produk', $code)->first();

        if (! $product && strlen($code) === 13 && str_starts_with($code, '0')) {
            $product = Product::where('kode_produk', substr($code, 1))->first();
        }

        if (! $product) {
            try {
                Flux::toast(variant: 'danger', text: "Produk dengan barcode [{$code}] tidak ditemukan!");
            } catch (\Throwable) {
            }
            $this->barcodeInput = '';

            return;
        }

        $this->selectProduct($product->id);
        $this->barcodeInput = '';

        try {
            Flux::toast(variant: 'success', text: "Produk [{$product->nama}] terpilih.");
        } catch (\Throwable) {
        }
    }

    public function selectProduct(string $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $this->selectedProductId = $product->id;
        $this->stok_etalase_baru = $product->stok_etalase;
        $this->stok_gudang_baru = $product->stok_gudang;
    }

    public function setKeterangan(string $text): void
    {
        $this->keterangan = $text;
    }

    public function saveAdjustment(StockService $stockService): void
    {
        if (! $this->selectedProductId) {
            try {
                Flux::toast(variant: 'warning', text: 'Pilih produk yang akan disesuaikan terlebih dahulu.');
            } catch (\Throwable) {
            }

            return;
        }

        $this->validate([
            'stok_etalase_baru' => ['required', 'integer', 'min:0'],
            'stok_gudang_baru' => ['required', 'integer', 'min:0'],
            'keterangan' => ['required', 'string', 'max:255'],
        ], [
            'stok_etalase_baru.required' => 'Stok etalase wajib diisi.',
            'stok_etalase_baru.min' => 'Stok etalase tidak boleh bernilai negatif.',
            'stok_gudang_baru.required' => 'Stok gudang wajib diisi.',
            'stok_gudang_baru.min' => 'Stok gudang tidak boleh bernilai negatif.',
            'keterangan.required' => 'Alasan / keterangan penyesuaian wajib diisi.',
        ]);

        $product = Product::findOrFail($this->selectedProductId);
        $oldTotal = $product->stok;
        $newEtalase = (int) $this->stok_etalase_baru;
        $newGudang = (int) $this->stok_gudang_baru;
        $newTotal = $newEtalase + $newGudang;
        $selisih = $newTotal - $oldTotal;

        try {
            $movement = $stockService->adjustStockDetailed(
                $product,
                $newEtalase,
                $newGudang,
                $this->keterangan
            );

            $selisihTeks = $selisih >= 0 ? "+{$selisih}" : "{$selisih}";
            Flux::toast(
                variant: 'success',
                text: "Penyesuaian stok [{$product->nama}] berhasil disimpan ({$selisihTeks} {$product->satuan}). Total stok kini: {$newTotal}."
            );

            // Refresh data produk
            $this->selectProduct($product->id);
        } catch (\Throwable $e) {
            try {
                Flux::toast(variant: 'danger', text: 'Gagal menyimpan penyesuaian: '.$e->getMessage());
            } catch (\Throwable) {
            }
        }
    }

    public function render()
    {
        $selectedProduct = null;
        if ($this->selectedProductId) {
            $selectedProduct = Product::with('golongan')->find($this->selectedProductId);
        }

        // Daftar produk untuk dropdown pencarian cepat
        $products = Product::orderBy('nama')->select('id', 'nama', 'kode_produk', 'satuan', 'stok')->get();

        // 10 riwayat penyesuaian stok terbaru
        $recentAdjustments = StockMovement::with('product')
            ->where('jenis', StockMovementType::Penyesuaian)
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.stok-penyesuaian', [
            'selectedProduct' => $selectedProduct,
            'products' => $products,
            'recentAdjustments' => $recentAdjustments,
        ]);
    }
}
