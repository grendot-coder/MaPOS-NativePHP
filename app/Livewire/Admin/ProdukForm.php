<?php

namespace App\Livewire\Admin;

use App\Enums\StockMovementType;
use App\Models\Golongan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\ProductRepository;
use App\Services\BarcodeService;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Form Produk - MaPOS')]
class ProdukForm extends Component
{
    public ?string $productId = null;

    public bool $isEditing = false;

    public string $golongan_id = '';

    public string $kode_produk = '';

    public string $nama = '';

    public string $satuan = 'pcs';

    public $harga_beli = 0;

    public $harga_retail = 0;

    public $harga_grosir_1 = null;

    public $harga_grosir_2 = null;

    public $harga_grosir_3 = null;

    public $diskon = 0;

    public $stok_etalase = 0;

    public $stok_gudang = 0;

    public $min_stok = 5;

    public string $barcodeNotice = '';

    public function mount(?string $id = null): void
    {
        if ($id) {
            $this->isEditing = true;
            $this->productId = $id;

            $product = Product::findOrFail($id);
            $this->golongan_id = $product->golongan_id;
            $this->kode_produk = $product->kode_produk;
            $this->nama = $product->nama;
            $this->satuan = $product->satuan;
            $this->harga_beli = $product->harga_beli;
            $this->harga_retail = $product->harga_retail;
            $this->harga_grosir_1 = $product->harga_grosir_1;
            $this->harga_grosir_2 = $product->harga_grosir_2;
            $this->harga_grosir_3 = $product->harga_grosir_3;
            $this->diskon = $product->diskon;
            $this->stok_etalase = $product->stok_etalase;
            $this->stok_gudang = $product->stok_gudang;
            $this->min_stok = $product->min_stok;

            $this->checkBarcodeValidity();
        } else {
            // Pilih golongan pertama sebagai default
            $first = Golongan::orderBy('kode_golongan')->first();
            if ($first) {
                $this->golongan_id = $first->id;
            }
        }
    }

    public function generateInternalBarcode(BarcodeService $service): void
    {
        if (! $this->golongan_id) {
            Flux::toast(variant: 'warning', text: 'Pilih Golongan terlebih dahulu sebelum membuat barcode internal.');

            return;
        }

        $golongan = Golongan::find($this->golongan_id);
        if (! $golongan) {
            return;
        }

        // Tentukan nomor urut berikutnya berdasarkan produk di golongan ini
        $count = Product::where('golongan_id', $golongan->id)->count() + 1;
        $this->kode_produk = $service->generateInternalCode($golongan->kode_golongan, $count, 0);

        $this->checkBarcodeValidity();
        Flux::toast(variant: 'success', text: "Kode barcode internal EAN-13 berhasil dibuat: {$this->kode_produk}");
    }

    public function updatedKodeProduk(): void
    {
        $this->checkBarcodeValidity();
    }

    public function checkBarcodeValidity(): void
    {
        $this->kode_produk = trim($this->kode_produk);
        if (empty($this->kode_produk)) {
            $this->barcodeNotice = '';

            return;
        }

        $service = app(BarcodeService::class);
        $type = $service->detectBarcodeType($this->kode_produk);

        if (strlen($this->kode_produk) === 13 && ctype_digit($this->kode_produk)) {
            if ($service->validateEan13($this->kode_produk)) {
                $this->barcodeNotice = '✅ Barcode EAN-13 valid (Check digit sesuai).';
            } else {
                $expected = $service->calculateEan13CheckDigit(substr($this->kode_produk, 0, 12));
                $this->barcodeNotice = "⚠️ Check digit EAN-13 tidak cocok! Digit terakhir seharusnya {$expected}.";
            }
        } else {
            $this->barcodeNotice = "ℹ️ Format terdeteksi: {$type}";
        }

        // Cek apakah barcode sudah terdaftar di produk lain
        $existing = Product::where('kode_produk', $this->kode_produk)
            ->when($this->productId, fn($q) => $q->where('id', '!=', $this->productId))
            ->first();

        if ($existing) {
            $this->barcodeNotice .= " ⚠️ PERHATIAN: Barcode ini sudah terdaftar pada produk [{$existing->nama}]!";
        }
    }

    public function save(ProductRepository $repository): void
    {
        $uniqueRule = Rule::unique('products', 'kode_produk');
        if ($this->isEditing && $this->productId) {
            $uniqueRule->ignore($this->productId);
        }

        $this->validate([
            'golongan_id' => ['required', 'exists:golongans,id'],
            'kode_produk' => ['required', 'string', 'max:50', $uniqueRule],
            'nama' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:20'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
            'harga_retail' => ['required', 'numeric', 'min:0'],
            'harga_grosir_1' => ['nullable', 'numeric', 'min:0'],
            'harga_grosir_2' => ['nullable', 'numeric', 'min:0'],
            'harga_grosir_3' => ['nullable', 'numeric', 'min:0'],
            'diskon' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stok_etalase' => ['required', 'integer', 'min:0'],
            'stok_gudang' => ['required', 'integer', 'min:0'],
            'min_stok' => ['required', 'integer', 'min:0'],
        ], [
            'kode_produk.required' => 'Kode barcode produk wajib diisi.',
            'kode_produk.unique' => 'Kode barcode ini sudah terdaftar untuk produk lain.',
            'golongan_id.required' => 'Pilih golongan produk.',
            'nama.required' => 'Nama produk wajib diisi.',
            'satuan.required' => 'Satuan produk wajib diisi.',
        ]);

        $totalStok = (int) $this->stok_etalase + (int) $this->stok_gudang;

        $data = [
            'golongan_id' => $this->golongan_id,
            'kode_produk' => $this->kode_produk,
            'nama' => $this->nama,
            'satuan' => $this->satuan,
            'harga_beli' => (float) $this->harga_beli,
            'harga_retail' => (float) $this->harga_retail,
            'harga_grosir_1' => $this->harga_grosir_1 ? (float) $this->harga_grosir_1 : null,
            'harga_grosir_2' => $this->harga_grosir_2 ? (float) $this->harga_grosir_2 : null,
            'harga_grosir_3' => $this->harga_grosir_3 ? (float) $this->harga_grosir_3 : null,
            'diskon' => (float) $this->diskon,
            'stok_etalase' => (int) $this->stok_etalase,
            'stok_gudang' => (int) $this->stok_gudang,
            'stok' => $totalStok,
            'min_stok' => (int) $this->min_stok,
        ];

        if ($this->isEditing && $this->productId) {
            $product = Product::findOrFail($this->productId);
            $selisihStok = $totalStok - $product->stok;

            $repository->update($product, $data);

            if ($selisihStok !== 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'jenis' => StockMovementType::Penyesuaian,
                    'jumlah' => $selisihStok,
                    'saldo_sesudah' => $totalStok,
                    'keterangan' => 'Penyesuaian stok saat edit data produk',
                ]);
            }

            Flux::toast(variant: 'success', text: "Data produk [{$product->nama}] berhasil diperbarui.");
        } else {
            $product = $repository->create($data);

            // Buat saldo awal di kartu stok
            StockMovement::create([
                'product_id' => $product->id,
                'jenis' => StockMovementType::Penyesuaian,
                'jumlah' => $totalStok,
                'saldo_sesudah' => $totalStok,
                'keterangan' => 'Saldo awal pendaftaran produk baru',
            ]);

            Flux::toast(variant: 'success', text: "Produk baru [{$product->nama}] berhasil ditambahkan.");
        }

        $this->redirect(route('admin.produk.index'), navigate: true);
    }

    public function render()
    {
        $golongans = Golongan::orderBy('kode_golongan')->get();

        $margin = 0;
        $hargaBeli = (float) ($this->harga_beli ?? 0);
        $hargaRetail = (float) ($this->harga_retail ?? 0);

        if ($hargaBeli > 0 && $hargaRetail > 0) {
            $margin = (($hargaRetail - $hargaBeli) / $hargaBeli) * 100;
        }

        $stokEtalase = (int) ($this->stok_etalase ?? 0);
        $stokGudang = (int) ($this->stok_gudang ?? 0);

        return view('livewire.admin.produk-form', [
            'golongans' => $golongans,
            'margin' => round($margin, 1),
            'totalStokHitung' => $stokEtalase + $stokGudang,
        ]);
    }
}
