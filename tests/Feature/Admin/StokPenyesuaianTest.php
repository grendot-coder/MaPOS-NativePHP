<?php

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Livewire\Admin\StokPenyesuaian;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can access penyesuaian stok page', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)
        ->get(route('admin.stok.penyesuaian'))
        ->assertOk()
        ->assertSee('Penyesuaian Stok (Stock Opname)');
});

test('kasir cannot access penyesuaian stok page', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($kasir)
        ->get(route('admin.stok.penyesuaian'))
        ->assertForbidden();
});

test('admin can scan barcode or select product to adjust stock', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::first();

    $initialTotal = $product->stok;

    $component = Livewire::actingAs($admin)
        ->test(StokPenyesuaian::class)
        ->set('barcodeInput', $product->kode_produk)
        ->call('scanBarcode')
        ->assertSet('selectedProductId', $product->id)
        ->assertSet('barcodeInput', '');

    // Koreksi stok: set etalase baru dan gudang baru
    $component->set('stok_etalase_baru', 20)
        ->set('stok_gudang_baru', 30)
        ->set('keterangan', 'Uji Coba Stock Opname')
        ->call('saveAdjustment')
        ->assertHasNoErrors();

    // Verifikasi stok produk terupdate
    $product->refresh();
    expect($product->stok_etalase)->toBe(20);
    expect($product->stok_gudang)->toBe(30);
    expect($product->stok)->toBe(50);

    // Verifikasi pencatatan StockMovement
    $movement = StockMovement::where('product_id', $product->id)
        ->where('keterangan', 'Uji Coba Stock Opname')
        ->latest('created_at')
        ->first();

    expect($movement)->not->toBeNull();
    expect($movement->jenis)->toBe(StockMovementType::Penyesuaian);
    expect($movement->jumlah)->toBe(50 - $initialTotal);
    expect($movement->saldo_sesudah)->toBe(50);
});

test('negative stock adjustment is rejected', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::first();

    Livewire::actingAs($admin)
        ->test(StokPenyesuaian::class)
        ->call('selectProduct', $product->id)
        ->set('stok_etalase_baru', -5)
        ->call('saveAdjustment')
        ->assertHasErrors(['stok_etalase_baru']);
});
