<?php

namespace App\Livewire\Admin;

use App\Models\Golongan;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\BarcodeService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Master Produk - MaPOS')]
class ProdukIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'golongan')]
    public string $selectedGolongan = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'nama';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    #[Url(as: 'limit')]
    public int $perPage = 25;

    public ?string $deletingId = null;

    public bool $showBarcodeModal = false;

    public string $previewBarcode = '';

    public string $previewNama = '';

    public string $previewHarga = '';

    public string $previewSvg = '';

    public function showBarcode(string $id, BarcodeService $service): void
    {
        $product = Product::findOrFail($id);
        $this->previewBarcode = $product->kode_produk;
        $this->previewNama = $product->nama;
        $this->previewHarga = 'Rp '.number_format($product->harga_retail, 0, ',', '.');
        $this->previewSvg = $service->renderSvg($product->kode_produk, 2, 55);
        $this->showBarcodeModal = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedGolongan(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(string $id, ProductRepository $repository): void
    {
        $product = Product::withCount('saleItems')->findOrFail($id);

        if ($product->sale_items_count > 0) {
            Flux::toast(
                variant: 'danger',
                text: "Produk [{$product->nama}] tidak dapat dihapus permanen karena sudah memiliki riwayat transaksi penjualan."
            );

            return;
        }

        $repository->delete($product);
        Flux::toast(variant: 'success', text: "Produk [{$product->nama}] berhasil dihapus.");
    }

    public function render(ProductRepository $repository)
    {
        $golongans = Golongan::orderBy('kode_golongan')->get();

        $products = Product::query()
            ->with('golongan')
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode_produk', 'like', "%{$search}%");
                });
            })
            ->when($this->selectedGolongan, function ($query, $gId) {
                $query->where('golongan_id', $gId);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.produk-index', [
            'products' => $products,
            'golongans' => $golongans,
        ]);
    }
}
