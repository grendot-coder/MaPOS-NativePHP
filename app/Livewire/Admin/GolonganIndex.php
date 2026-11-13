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
            'kode_golongan.required' => 'Kode golongan 2 digit wajib diisi.',
            'kode_golongan.digits' => 'Kode golongan harus persis 2 digit angka (contoh: 01, 02).',
            'kode_golongan.unique' => 'Kode golongan sudah digunakan.',
            'nama_golongan.required' => 'Nama golongan wajib diisi.',
        ]);

        if ($this->isEditing && $this->selectedId) {
            $golongan = Golongan::findOrFail($this->selectedId);
            $repository->update($golongan, [
                'kode_golongan' => $this->kode_golongan,
                'nama_golongan' => $this->nama_golongan,
            ]);
            Flux::toast(variant: 'success', text: 'Golongan berhasil diperbarui.');
        } else {
            $repository->create([
                'kode_golongan' => $this->kode_golongan,
                'nama_golongan' => $this->nama_golongan,
            ]);
            Flux::toast(variant: 'success', text: 'Golongan baru berhasil ditambahkan.');
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
                text: "Tidak dapat menghapus golongan [{$golongan->nama_golongan}] karena masih memiliki {$golongan->products_count} produk terdaftar."
            );

            return;
        }

        $repository->delete($golongan);
        Flux::toast(variant: 'success', text: 'Golongan berhasil dihapus.');
    }

    public function render()
    {
        $golongans = Golongan::withCount('products')
            ->orderBy('kode_golongan')
            ->get();

        return view('livewire.admin.golongan-index', [
            'golongans' => $golongans,
        ]);
    }
}
