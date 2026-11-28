<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Faktur Pembelian & Kulakan Baru - MaPOS')]
class PembelianForm extends Component
{
    // Faktur Header
    public string $no_faktur = '';

    public string $tanggal = '';

    public ?string $supplier_id = null;

    public string $nama_supplier = 'Supplier Umum';

    public string $status_pembayaran = 'lunas';

    public string $jatuh_tempo = '';

    public string $catatan = '';

    // Barcode scanner & pencarian produk
    public string $barcodeInput = '';

    public string $searchProduct = '';

    public string $missingBarcode = '';

    // Daftar barang faktur
    public array $items = [];

    // Modal Tambah Supplier Cepat
    public bool $showSupplierModal = false;

    public string $newSupplierNama = '';

    public string $newSupplierTelepon = '';

    public string $newSupplierAlamat = '';

    public string $newSupplierKontak = '';

    public function mount(PurchaseService $purchaseService): void
    {
        $this->no_faktur = $purchaseService->generateInvoiceNumber();
        $this->tanggal = date('Y-m-d');
    }

    public function updatedStatusPembayaran(string $value): void
    {
        if ($value === 'hutang' && empty($this->jatuh_tempo)) {
            $this->jatuh_tempo = date('Y-m-d', strtotime('+30 days'));
        }
    }

    public function updatedSupplierId(): void
    {
        if (! empty($this->supplier_id)) {
            $supplier = Supplier::find($this->supplier_id);
            if ($supplier) {
                $this->nama_supplier = $supplier->nama;
            }
        }
    }

    public function scanBarcode(): void
    {
        $code = trim($this->barcodeInput);
        if (empty($code)) {
            return;
        }

        // Cari exact kode_produk atau fallback UPC-A
        $product = Product::where('kode_produk', $code)->first();
        if (! $product && strlen($code) === 13 && str_starts_with($code, '0')) {
            $product = Product::where('kode_produk', substr($code, 1))->first();
        }

        if ($product) {
            $this->addItem($product);
            $this->barcodeInput = '';
            $this->missingBarcode = '';
        } else {
            $this->missingBarcode = $code;
            $this->addError('barcodeInput', "Produk dengan barcode [{$code}] tidak ditemukan.");
        }
    }

    public function selectProduct(string $productId): void
    {
        $product = Product::find($productId);
        if ($product) {
            $this->addItem($product);
            $this->searchProduct = '';
            $this->missingBarcode = '';
            $this->resetErrorBag('barcodeInput');
        }
    }

    protected function addItem(Product $product): void
    {
        // Cek jika produk sudah ada dalam items, tambahkan qty
        foreach ($this->items as $index => $item) {
            if ($item['product_id'] === $product->id) {
                $this->items[$index]['qty']++;
                $this->recalculateSubtotal($index);

                return;
            }
        }

        $hargaBeli = (float) $product->harga_beli;

        $this->items[] = [
            'product_id' => $product->id,
            'kode_produk' => $product->kode_produk,
            'nama' => $product->nama,
            'satuan' => $product->satuan,
            'qty' => 1,
            'lokasi_tujuan' => 'gudang', // Default restock masuk gudang
            'harga_beli' => $hargaBeli,
            'update_harga_beli_master' => true,
            'subtotal' => $hargaBeli * 1,
        ];
    }

    public function updateQty(int $index, $qty): void
    {
        $val = max(1, (int) $qty);
        if (isset($this->items[$index])) {
            $this->items[$index]['qty'] = $val;
            $this->recalculateSubtotal($index);
        }
    }

    public function updateHargaBeli(int $index, $harga): void
    {
        $val = max(0, (float) $harga);
        if (isset($this->items[$index])) {
            $this->items[$index]['harga_beli'] = $val;
            $this->recalculateSubtotal($index);
        }
    }

    public function removeItem(int $index): void
    {
        if (isset($this->items[$index])) {
            array_splice($this->items, $index, 1);
        }
    }

    protected function recalculateSubtotal(int $index): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['subtotal'] = (int) $this->items[$index]['qty'] * (float) $this->items[$index]['harga_beli'];
        }
    }

    public function getTotalQtyProperty(): int
    {
        return (int) array_sum(array_column($this->items, 'qty'));
    }

    public function getTotalTagihanProperty(): float
    {
        return (float) array_sum(array_column($this->items, 'subtotal'));
    }

    public function openSupplierModal(): void
    {
        $this->resetValidation([
            'newSupplierNama',
            'newSupplierTelepon',
            'newSupplierAlamat',
            'newSupplierKontak',
        ]);
        $this->newSupplierNama = '';
        $this->newSupplierTelepon = '';
        $this->newSupplierAlamat = '';
        $this->newSupplierKontak = '';
        $this->showSupplierModal = true;
    }

    public function saveSupplier(): void
    {
        $this->validate([
            'newSupplierNama' => ['required', 'string', 'max:100'],
            'newSupplierTelepon' => ['nullable', 'string', 'max:50'],
            'newSupplierAlamat' => ['nullable', 'string', 'max:500'],
            'newSupplierKontak' => ['nullable', 'string', 'max:100'],
        ], [
            'newSupplierNama.required' => 'Nama supplier wajib diisi.',
        ]);

        $supplier = Supplier::create([
            'nama' => trim($this->newSupplierNama),
            'telepon' => trim($this->newSupplierTelepon),
            'alamat' => trim($this->newSupplierAlamat),
            'nama_kontak' => trim($this->newSupplierKontak),
        ]);

        $this->supplier_id = $supplier->id;
        $this->nama_supplier = $supplier->nama;
        $this->showSupplierModal = false;

        try {
            Flux::toast(variant: 'success', text: "Supplier [{$supplier->nama}] berhasil ditambahkan.");
        } catch (\Throwable) {
        }
    }

    public function simpanFaktur(PurchaseService $purchaseService)
    {
        $this->validate([
            'no_faktur' => ['required', 'string', 'max:100'],
            'nama_supplier' => ['required', 'string', 'max:150'],
            'tanggal' => ['required', 'date'],
            'status_pembayaran' => ['required', 'in:lunas,hutang'],
            'jatuh_tempo' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga_beli' => ['required', 'numeric', 'min:0'],
            'items.*.lokasi_tujuan' => ['required', 'in:gudang,etalase'],
        ], [
            'no_faktur.required' => 'Nomor faktur wajib diisi.',
            'nama_supplier.required' => 'Nama supplier wajib diisi.',
            'items.min' => 'Faktur belum memiliki barang. Tambahkan minimal 1 barang kulakan.',
        ]);

        try {
            $headerData = [
                'no_faktur' => $this->no_faktur,
                'supplier_id' => $this->supplier_id,
                'nama_supplier' => $this->nama_supplier,
                'tanggal' => $this->tanggal,
                'status_pembayaran' => $this->status_pembayaran,
                'jatuh_tempo' => $this->status_pembayaran === 'hutang' ? $this->jatuh_tempo : null,
                'catatan' => $this->catatan,
            ];

            $purchase = $purchaseService->recordPurchase(
                user: Auth::user(),
                headerData: $headerData,
                items: $this->items
            );

            session()->flash('success', "Faktur pembelian #{$purchase->no_faktur} berhasil dibukukan dan stok telah bertambah.");

            return redirect()->route('admin.pembelian.index');
        } catch (\Throwable $e) {
            $this->addError('faktur', 'Gagal membukukan faktur: '.$e->getMessage());
        }
    }

    public function render()
    {
        $suppliers = Supplier::orderBy('nama')->get();

        $searchResults = collect();
        if (strlen(trim($this->searchProduct)) >= 2) {
            $q = trim($this->searchProduct);
            $searchResults = Product::where('status_aktif', true)
                ->where(function ($query) use ($q) {
                    $query->where('nama', 'like', "%{$q}%")
                        ->orWhere('kode_produk', 'like', "%{$q}%");
                })
                ->limit(8)
                ->get();
        }

        return view('livewire.admin.pembelian-form', [
            'suppliers' => $suppliers,
            'searchResults' => $searchResults,
        ]);
    }
}
