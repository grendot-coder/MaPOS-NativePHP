<?php

use App\Enums\UserRole;
use App\Livewire\Admin\PenggunaIndex;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can access pengguna page and kasir is forbidden', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($admin)->get(route('admin.pengguna.index'))->assertOk()->assertSee('Akun Pengguna &amp; Kasir', false);
    $this->actingAs($kasir)->get(route('admin.pengguna.index'))->assertForbidden();
});

test('admin can create, update, and delete user accounts with safety guards', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    // Create new kasir user
    Livewire::actingAs($admin)
        ->test(PenggunaIndex::class)
        ->call('create')
        ->set('name', 'Kasir Malam')
        ->set('email', 'kasir.malam@mapos.test')
        ->set('role', 'kasir')
        ->set('password', 'secret123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $created = User::where('email', 'kasir.malam@mapos.test')->first();
    expect($created)->not->toBeNull();
    expect($created->role)->toBe(UserRole::Kasir);
    expect(Hash::check('secret123', $created->password))->toBeTrue();

    // Update name without changing password
    Livewire::actingAs($admin)
        ->test(PenggunaIndex::class)
        ->call('edit', $created->id)
        ->set('name', 'Kasir Malam Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($created->fresh()->name)->toBe('Kasir Malam Updated');
    expect(Hash::check('secret123', $created->fresh()->password))->toBeTrue();

    // Cannot delete self
    Livewire::actingAs($admin)
        ->test(PenggunaIndex::class)
        ->call('delete', $admin->id);

    expect(User::find($admin->id))->not->toBeNull();

    // Can delete other user
    Livewire::actingAs($admin)
        ->test(PenggunaIndex::class)
        ->call('delete', $created->id);

    expect(User::find($created->id))->toBeNull();
});
