<?php

namespace App\Livewire\Admin;

use App\Models\Golongan;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Laporan Stok & Nilai Aset - MaPOS')]
class LaporanStok extends Component
{
    use WithPagination;

    public string $search = '';

    public string $selectedGolongan = '';

    #[Url(as: 'status')]
    public string $statusFilter = ''; // 'all', 'menipis', 'habis', 'aman'

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedGolongan(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /** @return Builder<Product> */
    protected function filteredProductsQuery(): Builder
    {
        $query = Product::with('golongan');

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nama', 'like', "%{$this->search}%")
                    ->orWhere('kode_produk', 'like', "%{$this->search}%");
            });
        }

        if (! empty($this->selectedGolongan)) {
            $query->where('golongan_id', $this->selectedGolongan);
        }

        if ($this->statusFilter === 'menipis') {
            $query->where('stok', '>', 0)->whereColumn('stok', '<=', 'min_stok');
        } elseif ($this->statusFilter === 'habis') {
            $query->where('stok', '<=', 0);
        } elseif ($this->statusFilter === 'aman') {
            $query->whereColumn('stok', '>', 'min_stok');
        }

        return $query;
    }

    public function exportCsv(): StreamedResponse
    {
        $products = $this->filteredProductsQuery()->orderBy('nama')->get();
        $filename = 'laporan-stok-aset-'.date('Ymd').'.csv';

        return response()->streamDownload(function () use ($products): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Kode Barcode',
                'Nama Produk',
                'Golongan',
                'Satuan',
                'Harga Beli',
                'Harga Retail',
                'Stok Etalase',
                'Stok Gudang',
                'Total Stok',
                'Min Stok',
                'Nilai Modal Aset',
                'Status Stok',
            ]);

            foreach ($products as $p) {
                $status = $p->stok <= 0 ? 'HABIS' : ($p->stok <= $p->min_stok ? 'MENIPIS' : 'AMAN');
                fputcsv($out, [
                    $p->kode_produk,
                    $p->nama,
                    $p->golongan?->nama_golongan ?? '-',
                    $p->satuan,
                    (float) $p->harga_beli,
                    (float) $p->harga_retail,
                    $p->stok_etalase,
                    $p->stok_gudang,
                    $p->stok,
                    $p->min_stok,
                    (float) $p->harga_beli * $p->stok,
                    $status,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        // 1. Hitung ringkasan aset untuk seluruh produk di database
        $allProducts = Product::all();
        $totalNilaiModal = $allProducts->sum(fn ($p) => (float) $p->harga_beli * (int) $p->stok);
        $totalNilaiRetail = $allProducts->sum(fn ($p) => (float) $p->harga_retail * (int) $p->stok);
        $totalPotensiLaba = max(0, $totalNilaiRetail - $totalNilaiModal);

        $totalMenipis = $allProducts->filter(fn ($p) => $p->stok > 0 && $p->stok <= $p->min_stok)->count();
        $totalHabis = $allProducts->filter(fn ($p) => $p->stok <= 0)->count();

        // 2. Query data dengan filter
        $products = $this->filteredProductsQuery()->orderBy('nama')->paginate(25);
        $golongans = Golongan::orderBy('kode_golongan')->get();

        return view('livewire.admin.laporan-stok', [
            'products' => $products,
            'golongans' => $golongans,
            'totalNilaiModal' => $totalNilaiModal,
            'totalNilaiRetail' => $totalNilaiRetail,
            'totalPotensiLaba' => $totalPotensiLaba,
            'totalMenipis' => $totalMenipis,
            'totalHabis' => $totalHabis,
        ]);
    }
}
