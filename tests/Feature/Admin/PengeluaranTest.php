<?php

use App\Enums\PengeluaranKategori;
use App\Enums\UserRole;
use App\Livewire\Admin\PengeluaranIndex;
use App\Models\Pengeluaran;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can access pengeluaran page', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)
        ->get(route('admin.pengeluaran.index'))
        ->assertOk()
        ->assertSee('Pengeluaran Biaya')
        ->assertSee('Biaya Operasional Toko')
        ->assertSee('Kasbon Karyawan');
});

test('kasir cannot access admin pengeluaran page', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($kasir)
        ->get(route('admin.pengeluaran.index'))
        ->assertForbidden();
});

test('admin can record operational expense', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(PengeluaranIndex::class)
        ->set('kategori', 'operasional')
        ->set('nominal', 35000)
        ->set('keterangan', 'Beli kantong plastik kresek hitam 3 pack')
        ->set('waktu', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    $expense = Pengeluaran::where('keterangan', 'Beli kantong plastik kresek hitam 3 pack')->first();
    expect($expense)->not->toBeNull();
    expect($expense->nominal)->toBe(35000.0);
    expect($expense->kategori)->toBe(PengeluaranKategori::Operasional);
    expect($expense->no_referensi)->toStartWith('EXP-');
});

test('admin can record employee kasbon', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(PengeluaranIndex::class)
        ->set('kategori', 'kasbon')
        ->set('nama_karyawan', 'Budi Santoso')
        ->set('nominal', 50000)
        ->set('keterangan', 'Pinjaman uang bensin')
        ->set('waktu', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    $kasbon = Pengeluaran::where('nama_karyawan', 'Budi Santoso')->first();
    expect($kasbon)->not->toBeNull();
    expect($kasbon->nominal)->toBe(50000.0);
    expect($kasbon->kategori)->toBe(PengeluaranKategori::Kasbon);
    expect($kasbon->no_referensi)->toStartWith('KSB-');
});

test('kasbon requires employee name validation', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(PengeluaranIndex::class)
        ->set('kategori', 'kasbon')
        ->set('nama_karyawan', '') // Kosong
        ->set('nominal', 50000)
        ->set('keterangan', 'Pinjaman uang')
        ->set('waktu', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['nama_karyawan']);
});

test('admin can delete pengeluaran', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $item = Pengeluaran::create([
        'no_referensi' => 'EXP-20261116-9999',
        'kategori' => PengeluaranKategori::Operasional,
        'nominal' => 15000,
        'keterangan' => 'Pengeluaran uji coba untuk dihapus',
        'user_id' => $admin->id,
        'waktu' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(PengeluaranIndex::class)
        ->call('delete', $item->id)
        ->assertHasNoErrors();

    expect(Pengeluaran::find($item->id))->toBeNull();
    // Verifikasi soft deleted
    expect(Pengeluaran::withTrashed()->find($item->id))->not->toBeNull();
});
