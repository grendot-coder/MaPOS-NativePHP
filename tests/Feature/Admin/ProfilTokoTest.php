<?php

use App\Enums\UserRole;
use App\Livewire\Admin\ProfilToko;
use App\Models\StoreProfile;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('store profile singleton defaults to valid initial values', function () {
    $profile = StoreProfile::getProfile();

    expect($profile)->not->toBeNull();
    expect($profile->nama_toko)->toBe('MAPOS MINIMARKET');
    expect($profile->ukuran_kertas)->toBe('58mm');
});

test('admin can access profil toko page', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)
        ->get(route('admin.profil-toko'))
        ->assertOk()
        ->assertSee('Profil Toko')
        ->assertSee('Pratinjau Struk Kasir');
});

test('kasir cannot access profil toko page', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($kasir)
        ->get(route('admin.profil-toko'))
        ->assertForbidden();
});

test('admin can update profil toko and receipt format', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(ProfilToko::class)
        ->set('nama_toko', 'TOKO BERKAH JAYA')
        ->set('slogan', 'Belanja Murah & Lengkap')
        ->set('alamat', 'Jl. Malioboro No. 45, Yogyakarta')
        ->set('telepon', '0812-9988-7766')
        ->set('email', 'kontak@berkahjaya.com')
        ->set('footer_struk', 'Terima kasih atas kunjungan Anda di Berkah Jaya!')
        ->set('ukuran_kertas', '80mm')
        ->call('save')
        ->assertHasNoErrors();

    $profile = StoreProfile::getProfile();
    expect($profile->nama_toko)->toBe('TOKO BERKAH JAYA');
    expect($profile->slogan)->toBe('Belanja Murah & Lengkap');
    expect($profile->alamat)->toBe('Jl. Malioboro No. 45, Yogyakarta');
    expect($profile->telepon)->toBe('0812-9988-7766');
    expect($profile->email)->toBe('kontak@berkahjaya.com');
    expect($profile->footer_struk)->toBe('Terima kasih atas kunjungan Anda di Berkah Jaya!');
    expect($profile->ukuran_kertas)->toBe('80mm');
});

test('profil toko validates required nama_toko and valid ukuran_kertas', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(ProfilToko::class)
        ->set('nama_toko', '')
        ->set('ukuran_kertas', 'invalid-size')
        ->call('save')
        ->assertHasErrors(['nama_toko', 'ukuran_kertas']);
});
