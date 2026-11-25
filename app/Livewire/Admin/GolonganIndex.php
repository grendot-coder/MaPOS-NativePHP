<?php

namespace App\Livewire\Admin;

use App\Models\Golongan;
use App\Repositories\GolonganRepository;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Master Golongan - MaPOS')]
class GolonganIndex extends Component
{
    public string $search = '';

    public ?string $selectedId = null;

    #[Rule('required|digits:2')]
    public string $kode_golongan = '';

    #[Rule('required|min:2|max:100')]
    public string $nama_golongan = '';

    public bool $showModal = false;

    public bool $isEditing = false;

    public function create(): void
    {
        $this->reset(['selectedId', 'kode_golongan', 'nama_golongan', 'isEditing']);
        $this->resetValidation();

        // Rekomendasi kode golongan berikutnya
        $lastGolongan = Golongan::orderBy('kode_golongan', 'desc')->first();
        if ($lastGolongan && is_numeric($lastGolongan->kode_golongan)) {
            $nextNumber = (int) $lastGolongan->kode_golongan + 1;
            $this->kode_golongan = str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);
        } else {
            $this->kode_golongan = '01';
        }

        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $golongan = Golongan::findOrFail($id);
        $this->selectedId = $golongan->id;
        $this->kode_golongan = $golongan->kode_golongan;
        $this->nama_golongan = $golongan->nama_golongan;
        $this->isEditing = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(GolonganRepository $repository): void
    {
        $uniqueRule = 'unique:golongans,kode_golongan'.($this->selectedId ? ','.$this->selectedId : '');

        $this->validate([
            'kode_golongan' => ['required', 'digits:2', $uniqueRule],
            'nama_golongan' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'kode_golongan.required' => 'Kode kategori 2 digit wajib diisi.',
            'kode_golongan.digits' => 'Kode kategori harus persis 2 digit angka (contoh: 01, 02).',
            'kode_golongan.unique' => 'Kode kategori sudah digunakan.',
            'nama_golongan.required' => 'Nama kategori wajib diisi.',
        ]);

        if ($this->isEditing && $this->selectedId) {
            $golongan = Golongan::findOrFail($this->selectedId);
            $repository->update($golongan, [
                'kode_golongan' => $this->kode_golongan,
                'nama_golongan' => $this->nama_golongan,
            ]);
            Flux::toast(variant: 'success', text: 'Kategori berhasil diperbarui.');
        } else {
            $repository->create([
                'kode_golongan' => $this->kode_golongan,
                'nama_golongan' => $this->nama_golongan,
            ]);
            Flux::toast(variant: 'success', text: 'Kategori baru berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->reset(['selectedId', 'kode_golongan', 'nama_golongan', 'isEditing']);
    }

    public function delete(string $id, GolonganRepository $repository): void
    {
        $golongan = Golongan::withCount('products')->findOrFail($id);

        if ($golongan->products_count > 0) {
            Flux::toast(
                variant: 'danger',
                text: "Kategori [{$golongan->nama_golongan}] tidak dapat dihapus karena masih digunakan oleh {$golongan->products_count} produk."
            );

            return;
        }

        $repository->delete($golongan);
        Flux::toast(variant: 'success', text: 'Kategori berhasil dihapus.');
    }

    public function render()
    {
        $golongans = Golongan::withCount('products')
            ->when($this->search, function ($q) {
                $q->where('nama_golongan', 'like', '%'.$this->search.'%')
                    ->orWhere('kode_golongan', 'like', '%'.$this->search.'%');
            })
            ->orderBy('kode_golongan')
            ->get();

        return view('livewire.admin.golongan-index', [
            'golongans' => $golongans,
        ]);
    }
}
