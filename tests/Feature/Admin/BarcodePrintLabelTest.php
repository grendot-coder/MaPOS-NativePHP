<?php

use App\Enums\UserRole;
use App\Livewire\Admin\BarcodePrintLabel;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can access barcode print label page', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)
        ->get(route('admin.barcode.print-label'))
        ->assertOk()
        ->assertSee('Cetak Label Barcode Produk');
});

test('kasir cannot access barcode print label page', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($kasir)
        ->get(route('admin.barcode.print-label'))
        ->assertForbidden();
});

test('admin can add product to queue and customize copies', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::first();

    Livewire::actingAs($admin)
        ->test(BarcodePrintLabel::class)
        ->call('addToQueue', $product->id)
        ->assertSee($product->nama)
        ->call('updateCopies', $product->id, 12);
});

test('admin can clear print queue', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::first();

    Livewire::actingAs($admin)
        ->test(BarcodePrintLabel::class)
        ->call('addToQueue', $product->id)
        ->assertSee($product->nama)
        ->call('clearQueue')
        ->assertSet('printQueue', []);
});

