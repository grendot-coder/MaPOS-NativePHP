<?php

namespace App\Livewire\Admin;

use App\Enums\PengeluaranKategori;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pengeluaran & Kasbon - MaPOS')]
class PengeluaranIndex extends Component
{
    use WithPagination;

    // Filter
    public string $filterKategori = '';
    public string $filterBulan = '';
    public string $search = '';

    // Modal Form State
    public bool $showCreateModal = false;
    public string $kategori = 'operasional';
    public string $nama_karyawan = '';
    public $nominal = '';
    public string $keterangan = '';
    public string $waktu = '';

    public function mount(): void
    {
        $this->filterBulan = date('Y-m');
        $this->waktu = now()->format('Y-m-d\TH:i');
    }

    public function updatedFilterKategori(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBulan(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(string $defaultKategori = 'operasional'): void
    {
        $this->resetValidation();
        $this->kategori = $defaultKategori;
        $this->nama_karyawan = '';
        $this->nominal = '';
        $this->keterangan = '';
        $this->waktu = now()->format('Y-m-d\TH:i');
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'kategori' => ['required', 'string', 'in:operasional,kasbon,gaji,lainnya'],
            'nama_karyawan' => [
                in_array($this->kategori, ['kasbon', 'gaji']) ? 'required' : 'nullable',
                'string',
                'max:100',
            ],
            'nominal' => ['required', 'numeric', 'min:500'],
            'keterangan' => ['required', 'string', 'max:255'],
            'waktu' => ['required', 'date'],
        ], [
            'nama_karyawan.required' => 'Nama karyawan wajib diisi untuk kasbon atau gaji.',
            'nominal.required' => 'Nominal pengeluaran wajib diisi.',
            'nominal.min' => 'Nominal minimal Rp 500.',
            'keterangan.required' => 'Keterangan pengeluaran wajib diisi.',
        ]);

        // Buat nomor referensi otomatis (EXP-... atau KSB-...)
        $prefix = $this->kategori === 'kasbon' ? 'KSB' : 'EXP';
        $dateCode = Carbon::parse($this->waktu)->format('Ymd');
        $countToday = Pengeluaran::whereDate('waktu', Carbon::parse($this->waktu)->toDateString())->count() + 1;
        $noRef = sprintf('%s-%s-%04d', $prefix, $dateCode, $countToday);

        $activeShift = \App\Models\CashShift::activeShift(auth()->id())->first();

        Pengeluaran::create([
            'no_referensi' => $noRef,
            'kategori' => $this->kategori,
            'nama_karyawan' => in_array($this->kategori, ['kasbon', 'gaji']) ? trim($this->nama_karyawan) : null,
            'nominal' => (float) $this->nominal,
            'keterangan' => trim($this->keterangan),
            'user_id' => auth()->id(),
            'cash_shift_id' => $activeShift?->id,
            'waktu' => Carbon::parse($this->waktu),
        ]);

        $this->showCreateModal = false;

        $label = $this->kategori === 'kasbon' ? 'Kasbon karyawan' : 'Pengeluaran biaya';
        Flux::toast(variant: 'success', text: "{$label} senilai Rp " . number_format((float)$this->nominal, 0, ',', '.') . " berhasil dicatat.");
    }

    public function delete(string $id): void
    {
        $item = Pengeluaran::findOrFail($id);
        $item->delete();

        Flux::toast(variant: 'success', text: "Data pengeluaran [{$item->no_referensi}] berhasil dihapus.");
    }

    public function render()
    {
        // Query untuk tabel dengan filter
        $query = Pengeluaran::with('user')
            ->when($this->filterKategori, fn($q) => $q->where('kategori', $this->filterKategori))
            ->when($this->filterBulan, function ($q) {
                [$year, $month] = explode('-', $this->filterBulan);
                $q->whereYear('waktu', $year)->whereMonth('waktu', $month);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('no_referensi', 'like', "%{$this->search}%")
                        ->orWhere('keterangan', 'like', "%{$this->search}%")
                        ->orWhere('nama_karyawan', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('waktu', 'desc');

        $pengeluarans = $query->paginate(15);

        // Statistik ringkasan bulan yang dipilih
        [$selectedYear, $selectedMonth] = explode('-', $this->filterBulan ?: date('Y-m'));
        $statsBase = Pengeluaran::whereYear('waktu', $selectedYear)->whereMonth('waktu', $selectedMonth);

        $totalOperasional = (clone $statsBase)->where('kategori', PengeluaranKategori::Operasional)->sum('nominal');
        $totalKasbon = (clone $statsBase)->where('kategori', PengeluaranKategori::Kasbon)->sum('nominal');
        $totalSemua = (clone $statsBase)->sum('nominal');

        return view('livewire.admin.pengeluaran-index', [
            'pengeluarans' => $pengeluarans,
            'kategoriList' => PengeluaranKategori::cases(),
            'totalOperasional' => $totalOperasional,
            'totalKasbon' => $totalKasbon,
            'totalSemua' => $totalSemua,
        ]);
    }
}
