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
#[Title('Cetak Label Barcode Produk - MaPOS')]
class BarcodePrintLabel extends Component
{
    public string $search = '';

    public string $selectedGolongan = '';

    /**
     * Antrean produk yang akan dicetak labelnya:
     * id => ['id', 'kode_produk', 'nama', 'harga_retail', 'copies', 'svg']
     *
     * @var array<string, array{id: string, kode_produk: string, nama: string, harga_retail: float, copies: int, svg: string}>
     */
    public array $printQueue = [];

    // Pengaturan Tampilan Label
    public string $labelFormat = 'grid3'; // 'grid3' (3 kolom), 'grid2' (2 kolom), 'thermal' (1 kolom roll)

    public bool $showPrice = true;

    public bool $showStoreName = true;

    public string $storeName = 'MaPOS MART';

    public function mount(BarcodeService $service, ?string $product_id = null): void
    {
        $productId = $product_id ?? request()->query('product_id');
        $code = request()->query('code');

        if ($productId) {
            $this->addToQueue($productId, $service);
        } elseif ($code) {
            $product = Product::where('kode_produk', $code)->first();
            if ($product) {
                $this->addToQueue($product->id, $service);
            } else {
                $svg = $service->renderSvg($code, 2, 45);
                $this->printQueue['custom_'.$code] = [
                    'id' => 'custom_'.$code,
                    'kode_produk' => $code,
                    'nama' => 'Barcode: '.$code,
                    'harga_retail' => 0,
                    'copies' => 5,
                    'svg' => $svg,
                ];
                try {
                    Flux::toast(variant: 'success', text: "Barcode [{$code}] ditambahkan ke antrean cetak.");
                } catch (\Throwable) {
                }
            }
        }
    }

    public function addToQueue(string $productId, BarcodeService $service): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        if (isset($this->printQueue[$productId])) {
            $this->printQueue[$productId]['copies'] += 1;
        } else {
            $svg = $service->renderSvg($product->kode_produk, 2, 45);

            $this->printQueue[$productId] = [
                'id' => $product->id,
                'kode_produk' => $product->kode_produk,
                'nama' => $product->nama,
                'harga_retail' => (float) $product->harga_retail,
                'copies' => 5, // default 5 lembar label
                'svg' => $svg,
            ];
        }

        try {
            Flux::toast(variant: 'success', text: "Produk [{$product->nama}] ditambahkan ke antrean cetak.");
        } catch (\Throwable) {
            // Silently continue if running in non-HTTP context
        }
    }

    public function addAllFiltered(BarcodeService $service): void
    {
        $products = Product::query()
            ->when($this->search, function ($q, $search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_produk', 'like', "%{$search}%");
            })
            ->when($this->selectedGolongan, fn ($q, $g) => $q->where('golongan_id', $g))
            ->limit(20)
            ->get();

        foreach ($products as $product) {
            if (! isset($this->printQueue[$product->id])) {
                $this->printQueue[$product->id] = [
                    'id' => $product->id,
                    'kode_produk' => $product->kode_produk,
                    'nama' => $product->nama,
                    'harga_retail' => (float) $product->harga_retail,
                    'copies' => 3,
                    'svg' => $service->renderSvg($product->kode_produk, 2, 45),
                ];
            }
        }

        try {
            Flux::toast(variant: 'success', text: count($products).' produk ditambahkan ke antrean cetak.');
        } catch (\Throwable) {
            // Silently continue
        }
    }

    public function removeFromQueue(string $productId): void
    {
        unset($this->printQueue[$productId]);
    }

    public function clearQueue(): void
    {
        $this->printQueue = [];
    }

    public function updateCopies(string $productId, int $copies): void
    {
        if (isset($this->printQueue[$productId])) {
            $this->printQueue[$productId]['copies'] = max(1, min(100, $copies));
        }
    }

    public function render()
    {
        $golongans = Golongan::orderBy('kode_golongan')->get();

        $availableProducts = Product::query()
            ->when($this->search, function ($q, $search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_produk', 'like', "%{$search}%");
            })
            ->when($this->selectedGolongan, fn ($q, $g) => $q->where('golongan_id', $g))
            ->orderBy('nama')
            ->limit(10)
            ->get();

        // Hitung total seluruh stiker yang akan dicetak
        $totalStickers = 0;
        foreach ($this->printQueue as $item) {
            $totalStickers += $item['copies'];
        }

        return view('livewire.admin.barcode-print-label', [
            'golongans' => $golongans,
            'availableProducts' => $availableProducts,
            'totalStickers' => $totalStickers,
        ]);
    }
}
