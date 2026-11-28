<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Laporan Penjualan - MaPOS')]
class LaporanPenjualan extends Component
{
    use WithPagination;

    public string $period = 'today';

    public string $startDate = '';

    public string $endDate = '';

    public string $selectedKasir = '';

    // Modal Detail & Cetak Ulang Struk
    public bool $showReceiptModal = false;

    public ?string $selectedSaleId = null;

    public function mount(): void
    {
        $this->setPeriod('today');
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;

        switch ($period) {
            case 'today':
                $this->startDate = Carbon::today()->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = Carbon::yesterday()->format('Y-m-d');
                $this->endDate = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            default:
                break;
        }

        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->period = 'custom';
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->period = 'custom';
        $this->resetPage();
    }

    public function updatingSelectedKasir(): void
    {
        $this->resetPage();
    }

    public function showReceipt(string $saleId): void
    {
        $this->selectedSaleId = $saleId;
        $this->showReceiptModal = true;
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->selectedSaleId = null;
    }

    /** @return Builder<Sale> */
    protected function filteredSalesQuery(): Builder
    {
        $query = Sale::with(['kasir', 'cashShift', 'customer', 'dropshipper', 'items.product']);

        if (! empty($this->startDate)) {
            $query->whereDate('waktu', '>=', $this->startDate);
        }
        if (! empty($this->endDate)) {
            $query->whereDate('waktu', '<=', $this->endDate);
        }
        if (! empty($this->selectedKasir)) {
            $query->where('kasir_id', $this->selectedKasir);
        }

        return $query;
    }

    public function exportCsv(): StreamedResponse
    {
        $sales = $this->filteredSalesQuery()->latest('waktu')->get();
        $filename = 'laporan-penjualan-'.($this->startDate ?: 'all').'-sd-'.($this->endDate ?: 'all').'.csv';

        return response()->streamDownload(function () use ($sales): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'No. Transaksi',
                'Waktu',
                'Kasir / Petugas',
                'Member',
                'Metode Bayar',
                'Status',
                'Total Qty',
                'Total Belanja',
                'Bayar',
                'Kembali',
                'Sisa Piutang',
            ]);

            foreach ($sales as $s) {
                fputcsv($out, [
                    $s->no_transaksi,
                    $s->waktu->format('Y-m-d H:i:s'),
                    $s->nama_kasir_struk,
                    $s->customer?->nama ?? '-',
                    strtoupper($s->metode_pembayaran ?? 'cash'),
                    strtoupper($s->status_pembayaran ?? 'lunas'),
                    $s->items->sum('qty'),
                    (float) $s->total,
                    (float) $s->bayar,
                    (float) $s->kembali,
                    (float) $s->sisa_piutang,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $query = $this->filteredSalesQuery();

        // Statistik
        $statsQuery = clone $query;
        $totalOmzet = (clone $statsQuery)->sum('total');
        $totalTransaksi = (clone $statsQuery)->count();
        $totalItemTerjual = (clone $statsQuery)
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->sum('sale_items.qty');
        $rataRataBelanja = $totalTransaksi > 0 ? $totalOmzet / $totalTransaksi : 0;

        // Top 10 Produk Terlaris pada periode terpilih
        $filteredSaleIds = (clone $statsQuery)->select('sales.id');
        $topProducts = SaleItem::whereIn('sale_id', $filteredSaleIds)
            ->selectRaw('kode_produk, nama_produk, SUM(qty) as total_qty, SUM(subtotal) as total_omzet')
            ->groupBy('kode_produk', 'nama_produk')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Analisis Jam Sibuk (Peak Hours)
        $hourStats = [];
        foreach ((clone $statsQuery)->select('waktu', 'total')->get() as $row) {
            $hour = (int) $row->waktu->format('G');
            if (! isset($hourStats[$hour])) {
                $hourStats[$hour] = [
                    'hour' => $hour,
                    'label' => sprintf('%02d:00', $hour),
                    'count' => 0,
                    'omzet' => 0.0,
                ];
            }
            $hourStats[$hour]['count']++;
            $hourStats[$hour]['omzet'] += (float) $row->total;
        }
        ksort($hourStats);
        $peakHours = array_values($hourStats);
        $maxHourCount = ! empty($peakHours) ? max(array_column($peakHours, 'count')) : 0;

        $sales = $query->latest('waktu')->paginate(15);

        $selectedSale = null;
        if ($this->selectedSaleId) {
            $selectedSale = Sale::with(['items', 'kasir', 'cashShift', 'customer', 'dropshipper'])->find($this->selectedSaleId);
        }

        // Daftar kasir untuk filter
        $kasirList = User::whereIn('role', [UserRole::Kasir, UserRole::Admin])->orderBy('name')->get();

        return view('livewire.admin.laporan-penjualan', [
            'sales' => $sales,
            'selectedSale' => $selectedSale,
            'kasirList' => $kasirList,
            'totalOmzet' => $totalOmzet,
            'totalTransaksi' => $totalTransaksi,
            'totalItemTerjual' => $totalItemTerjual,
            'rataRataBelanja' => $rataRataBelanja,
            'topProducts' => $topProducts,
            'peakHours' => $peakHours,
            'maxHourCount' => $maxHourCount,
        ]);
    }
}
