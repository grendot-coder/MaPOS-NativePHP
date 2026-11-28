<?php

namespace App\Livewire\Admin;

use App\Models\Dropshipper;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Master Dropshipper - MaPOS')]
class DropshipperIndex extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public bool $isEditing = false;

    public ?string $editingId = null;

    public string $kode_dropshipper = '';

    public string $nama = '';

    public string $telepon = '';

    public string $alamat = '';

    public $diskon_khusus = 0;

    public bool $status_aktif = true;

    public function create(): void
    {
        $this->resetValidation();
        $this->isEditing = false;
        $this->editingId = null;
        $this->kode_dropshipper = Dropshipper::generateCode();
        $this->nama = '';
        $this->telepon = '';
        $this->alamat = '';
        $this->diskon_khusus = 0;
        $this->status_aktif = true;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $this->resetValidation();
        $dropshipper = Dropshipper::findOrFail($id);
        $this->isEditing = true;
        $this->editingId = $dropshipper->id;
        $this->kode_dropshipper = $dropshipper->kode_dropshipper;
        $this->nama = $dropshipper->nama;
        $this->telepon = $dropshipper->telepon ?? '';
        $this->alamat = $dropshipper->alamat ?? '';
        $this->diskon_khusus = (float) $dropshipper->diskon_khusus;
        $this->status_aktif = (bool) $dropshipper->status_aktif;
        $this->showModal = true;
    }

    public function save(): void
    {
        $uniqueRule = Rule::unique('dropshippers', 'kode_dropshipper');
        if ($this->isEditing && $this->editingId) {
            $uniqueRule->ignore($this->editingId);
        }

        $this->validate([
            'kode_dropshipper' => ['required', 'string', 'max:20', $uniqueRule],
            'nama' => ['required', 'string', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'diskon_khusus' => ['required', 'numeric', 'min:0', 'max:100'],
            'status_aktif' => ['boolean'],
        ], [
            'kode_dropshipper.required' => 'Kode dropshipper wajib diisi.',
            'kode_dropshipper.unique' => 'Kode dropshipper sudah digunakan.',
            'nama.required' => 'Nama toko / reseller dropshipper wajib diisi.',
            'diskon_khusus.max' => 'Diskon khusus maksimal 100%.',
        ]);

        $data = [
            'kode_dropshipper' => trim($this->kode_dropshipper),
            'nama' => trim($this->nama),
            'telepon' => filled($this->telepon) ? trim($this->telepon) : null,
            'alamat' => filled($this->alamat) ? trim($this->alamat) : null,
            'diskon_khusus' => (float) $this->diskon_khusus,
            'status_aktif' => (bool) $this->status_aktif,
        ];

        if ($this->isEditing && $this->editingId) {
            Dropshipper::findOrFail($this->editingId)->update($data);
            $msg = 'Data dropshipper berhasil diperbarui.';
        } else {
            Dropshipper::create($data);
            $msg = 'Dropshipper baru berhasil ditambahkan.';
        }

        $this->showModal = false;

        try {
            Flux::toast(variant: 'success', text: $msg);
        } catch (\Throwable) {
        }
    }

    public function delete(string $id): void
    {
        $dropshipper = Dropshipper::withCount('sales')->findOrFail($id);
        if ($dropshipper->sales_count > 0) {
            $dropshipper->update(['status_aktif' => false]);
            try {
                Flux::toast(variant: 'warning', text: "Dropshipper [{$dropshipper->nama}] memiliki riwayat order sehingga dinonaktifkan.");
            } catch (\Throwable) {
            }

            return;
        }

        $dropshipper->delete();

        try {
            Flux::toast(variant: 'success', text: 'Dropshipper berhasil dihapus.');
        } catch (\Throwable) {
        }
    }

    public function render()
    {
        $dropshippers = Dropshipper::withCount('sales')
            ->when($this->search, function ($q, $s): void {
                $q->where(function ($sub) use ($s): void {
                    $sub->where('nama', 'like', "%{$s}%")
                        ->orWhere('kode_dropshipper', 'like', "%{$s}%")
                        ->orWhere('telepon', 'like', "%{$s}%");
                });
            })
            ->orderBy('kode_dropshipper')
            ->get();

        return view('livewire.admin.dropshipper-index', [
            'dropshippers' => $dropshippers,
        ]);
    }
}
