<?php

namespace App\Livewire\Admin;

use App\Models\Golongan;
use App\Models\Product;
use App\Services\BarcodeService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Generator & Validasi Barcode - MaPOS')]
class BarcodeGenerator extends Component
{
    public string $barcode = '8998866200233';
    public ?string $selectedProductId = null;
    public ?string $selectedGolonganId = null;

    public string $detectedType = '';
    public bool $isValidEan13 = false;
    public string $validationMessage = '';
    public string $barcodeSvg = '';

    public function mount(BarcodeService $service): void
    {
        $firstGolongan = Golongan::orderBy('kode_golongan')->first();
        if ($firstGolongan) {
            $this->selectedGolonganId = $firstGolongan->id;
        }

        $this->updateBarcodePreview($service);
    }

    public function updatedBarcode(BarcodeService $service): void
    {
        $this->updateBarcodePreview($service);
    }

    public function updatedSelectedProductId(BarcodeService $service): void
    {
        if ($this->selectedProductId) {
            $product = Product::find($this->selectedProductId);
            if ($product) {
                $this->barcode = $product->kode_produk;
                $this->updateBarcodePreview($service);
            }
        }
    }

    public function generateInternal(BarcodeService $service): void
    {
        if (! $this->selectedGolonganId) {
            Flux::toast(variant: 'warning', text: 'Pilih golongan terlebih dahulu.');
            return;
        }

        $golongan = Golongan::find($this->selectedGolonganId);
        if ($golongan) {
            $seq = Product::where('golongan_id', $golongan->id)->count() + 1;
            $this->barcode = $service->generateInternalCode($golongan->kode_golongan, $seq, 0);
            $this->selectedProductId = null;
            $this->updateBarcodePreview($service);
            Flux::toast(variant: 'success', text: "Barcode internal EAN-13 berhasil dibuat: {$this->barcode}");
        }
    }

    public function updateBarcodePreview(BarcodeService $service): void
    {
        $this->barcode = trim($this->barcode);

        if (empty($this->barcode)) {
            $this->barcodeSvg = '';
            $this->detectedType = '-';
            $this->isValidEan13 = false;
            $this->validationMessage = 'Silakan masukkan kode barcode.';
            return;
        }

        $this->detectedType = $service->detectBarcodeType($this->barcode);
        $this->isValidEan13 = $service->validateEan13($this->barcode);

        if (strlen($this->barcode) === 13 && ctype_digit($this->barcode)) {
            if ($this->isValidEan13) {
                $this->validationMessage = '✅ Barcode EAN-13 Valid (Check digit sesuai bobot 1 & 3)';
            } else {
                $expected = $service->calculateEan13CheckDigit(substr($this->barcode, 0, 12));
                $this->validationMessage = "⚠️ Check digit EAN-13 tidak valid! Digit terakhir seharusnya [{$expected}]";
            }
        } else {
            $this->validationMessage = "ℹ️ Terdeteksi sebagai format {$this->detectedType}";
        }

        try {
            $this->barcodeSvg = $service->renderSvg($this->barcode, 2, 60);
        } catch (\Throwable $e) {
            $this->barcodeSvg = '';
            $this->validationMessage = 'Gagal merender barcode: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $products = Product::orderBy('nama')->get();
        $golongans = Golongan::orderBy('kode_golongan')->get();

        return view('livewire.admin.barcode-generator', [
            'products' => $products,
            'golongans' => $golongans,
        ]);
    }
}
