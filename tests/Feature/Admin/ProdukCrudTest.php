<?php

use App\Enums\UserRole;
use App\Livewire\Admin\ProdukForm;
use App\Livewire\Admin\ProdukIndex;
use App\Models\Golongan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can view products on smartpos table', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)
        ->get(route('admin.produk.index'))
        ->assertOk()
        ->assertSee('Master Produk')
        ->assertSee('Filter Golongan');
});

test('admin can search products by name or barcode', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(ProdukIndex::class)
        ->set('search', 'Indomie')
        ->assertSee('Indomie Mi Goreng')
        ->set('search', '8996001600269')
        ->assertSee('Air Mineral Aqua');
});

test('admin can filter products by golongan', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $foodGolongan = Golongan::where('kode_golongan', '02')->first();

    Livewire::actingAs($admin)
        ->test(ProdukIndex::class)
        ->set('selectedGolongan', $foodGolongan->id)
        ->assertSee('Indomie')
        ->assertDontSee('Pasta Gigi Pepsodent');
});

test('admin can create a new product and stock movement is recorded', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $golongan = Golongan::first();

    Livewire::actingAs($admin)
        ->test(ProdukForm::class)
        ->set('golongan_id', $golongan->id)
        ->set('kode_produk', '8999999123456')
        ->set('nama', 'Produk Uji Coba Baru')
        ->set('satuan', 'pcs')
        ->set('harga_beli', 5000)
        ->set('harga_retail', 7500)
        ->set('stok_etalase', 10)
        ->set('stok_gudang', 20)
        ->set('min_stok', 5)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.produk.index'));

    $product = Product::where('kode_produk', '8999999123456')->first();
    expect($product)->not->toBeNull();
    expect($product->nama)->toBe('Produk Uji Coba Baru');
    expect($product->stok)->toBe(30);

    // Verifikasi saldo awal dicatat di kartu stok
    $movement = StockMovement::where('product_id', $product->id)->first();
    expect($movement)->not->toBeNull();
    expect($movement->jumlah)->toBe(30);
    expect($movement->saldo_sesudah)->toBe(30);
});

test('admin can generate internal barcode automatically', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $golongan = Golongan::where('kode_golongan', '01')->first();

    $component = Livewire::actingAs($admin)
        ->test(ProdukForm::class)
        ->set('golongan_id', $golongan->id)
        ->call('generateInternalBarcode');

    $generatedCode = $component->get('kode_produk');
    expect(strlen($generatedCode))->toBe(13);
    expect(str_starts_with($generatedCode, '20001'))->toBeTrue();
});

test('admin can save wholesale min qty thresholds and promo dates on product', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $golongan = Golongan::first();

    Livewire::actingAs($admin)
        ->test(ProdukForm::class)
        ->set('golongan_id', $golongan->id)
        ->set('kode_produk', '8999999777888')
        ->set('nama', 'Produk Grosir Promo')
        ->set('satuan', 'pcs')
        ->set('harga_beli', 8000)
        ->set('harga_retail', 12000)
        ->set('harga_grosir_1', 11000)
        ->set('min_qty_grosir_1', 6)
        ->set('harga_grosir_2', 10000)
        ->set('min_qty_grosir_2', 24)
        ->set('diskon', 5)
        ->set('promo_mulai', '2026-11-01')
        ->set('promo_sampai', '2026-11-30')
        ->set('stok_etalase', 50)
        ->set('stok_gudang', 0)
        ->set('min_stok', 5)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('kode_produk', '8999999777888')->first();
    expect($product->min_qty_grosir_1)->toBe(6);
    expect($product->min_qty_grosir_2)->toBe(24);
    expect($product->promo_mulai?->format('Y-m-d'))->toBe('2026-11-01');
    expect($product->promo_sampai?->format('Y-m-d'))->toBe('2026-11-30');
});
