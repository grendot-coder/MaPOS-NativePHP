<?php

namespace App\Livewire\Admin;

use App\Models\StoreProfile;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Profil Toko - MaPOS')]
class ProfilToko extends Component
{
    public string $nama_toko = '';
    public string $slogan = '';
    public string $alamat = '';
    public string $telepon = '';
    public string $email = '';
    public string $website = '';
    public string $footer_struk = '';
    public string $ukuran_kertas = '58mm';

    public function mount(): void
    {
        $profile = StoreProfile::getProfile();

        $this->nama_toko = $profile->nama_toko ?? 'MAPOS MINIMARKET';
        $this->slogan = $profile->slogan ?? 'Sistem Kasir & Manajemen POS';
        $this->alamat = $profile->alamat ?? 'Jl. Soedirman No. 10';
        $this->telepon = $profile->telepon ?? '(085) 555-555-555';
        $this->email = $profile->email ?? '';
        $this->website = $profile->website ?? '';
        $this->footer_struk = $profile->footer_struk ?? 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan';
        $this->ukuran_kertas = $profile->ukuran_kertas ?? '58mm';
    }

    public function rules(): array
    {
        return [
            'nama_toko' => ['required', 'string', 'max:100'],
            'slogan' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'website' => ['nullable', 'string', 'max:100'],
            'footer_struk' => ['nullable', 'string', 'max:500'],
            'ukuran_kertas' => ['required', 'in:58mm,80mm'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_toko.required' => 'Nama toko wajib diisi.',
            'nama_toko.max' => 'Nama toko maksimal 100 karakter.',
            'ukuran_kertas.in' => 'Pilihan ukuran kertas harus 58mm atau 80mm.',
            'email.email' => 'Format alamat email tidak valid.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $profile = StoreProfile::getProfile();
        $profile->update($validated);

        try {
            Flux::toast(variant: 'success', text: 'Profil toko dan format struk berhasil diperbarui.');
        } catch (\Throwable) {}
    }

    public function render()
    {
        return view('livewire.admin.profil-toko');
    }
}
