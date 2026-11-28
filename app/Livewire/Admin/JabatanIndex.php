<?php

namespace App\Livewire\Admin;

use App\Models\Jabatan;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Master Jabatan - MaPOS')]
class JabatanIndex extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public bool $isEditing = false;

    public ?string $editingId = null;

    public string $kode_jabatan = '';

    public string $nama_jabatan = '';

    public $gaji_pokok_default = 0;

    public $tunjangan_default = 0;

    public string $deskripsi = '';

    public function create(): void
    {
        $this->resetValidation();
        $this->isEditing = false;
        $this->editingId = null;
        $this->kode_jabatan = Jabatan::generateCode();
        $this->nama_jabatan = '';
        $this->gaji_pokok_default = 2000000;
        $this->tunjangan_default = 0;
        $this->deskripsi = '';
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $this->resetValidation();
        $jabatan = Jabatan::findOrFail($id);
        $this->isEditing = true;
        $this->editingId = $jabatan->id;
        $this->kode_jabatan = $jabatan->kode_jabatan;
        $this->nama_jabatan = $jabatan->nama_jabatan;
        $this->gaji_pokok_default = (float) $jabatan->gaji_pokok_default;
        $this->tunjangan_default = (float) $jabatan->tunjangan_default;
        $this->deskripsi = $jabatan->deskripsi ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $uniqueRule = Rule::unique('jabatans', 'kode_jabatan');
        if ($this->isEditing && $this->editingId) {
            $uniqueRule->ignore($this->editingId);
        }

        $this->validate([
            'kode_jabatan' => ['required', 'string', 'max:20', $uniqueRule],
            'nama_jabatan' => ['required', 'string', 'max:100'],
            'gaji_pokok_default' => ['required', 'numeric', 'min:0'],
            'tunjangan_default' => ['required', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ], [
            'kode_jabatan.required' => 'Kode jabatan wajib diisi.',
            'kode_jabatan.unique' => 'Kode jabatan sudah digunakan.',
            'nama_jabatan.required' => 'Nama jabatan wajib diisi.',
        ]);

        $data = [
            'kode_jabatan' => trim($this->kode_jabatan),
            'nama_jabatan' => trim($this->nama_jabatan),
            'gaji_pokok_default' => (float) $this->gaji_pokok_default,
            'tunjangan_default' => (float) $this->tunjangan_default,
            'deskripsi' => filled($this->deskripsi) ? trim($this->deskripsi) : null,
        ];

        if ($this->isEditing && $this->editingId) {
            Jabatan::findOrFail($this->editingId)->update($data);
            $msg = 'Data jabatan berhasil diperbarui.';
        } else {
            Jabatan::create($data);
            $msg = 'Jabatan baru berhasil ditambahkan.';
        }

        $this->showModal = false;

        try {
            Flux::toast(variant: 'success', text: $msg);
        } catch (\Throwable) {
        }
    }

    public function delete(string $id): void
    {
        $jabatan = Jabatan::withCount('employees')->findOrFail($id);
        if ($jabatan->employees_count > 0) {
            try {
                Flux::toast(variant: 'danger', text: "Jabatan [{$jabatan->nama_jabatan}] tidak dapat dihapus karena masih digunakan oleh {$jabatan->employees_count} karyawan.");
            } catch (\Throwable) {
            }

            return;
        }

        $jabatan->delete();

        try {
            Flux::toast(variant: 'success', text: 'Jabatan berhasil dihapus.');
        } catch (\Throwable) {
        }
    }

    public function render()
    {
        $jabatans = Jabatan::withCount('employees')
            ->when($this->search, function ($q, $s) {
                $q->where('nama_jabatan', 'like', "%{$s}%")
                    ->orWhere('kode_jabatan', 'like', "%{$s}%");
            })
            ->orderBy('kode_jabatan')
            ->get();

        return view('livewire.admin.jabatan-index', [
            'jabatans' => $jabatans,
        ]);
    }
}
