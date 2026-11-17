<?php

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Livewire\Admin\PembelianForm;
use App\Livewire\Admin\PembelianIndex;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can access pembelian index page', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)
        ->get(route('admin.pembelian.index'))
        ->assertOk()
        ->assertSee('Pembelian &amp; Restock Barang', false)
        ->assertSee('Total Belanja / Kulakan');
});

test('kasir cannot access admin pembelian page', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($kasir)
        ->get(route('admin.pembelian.index'))
        ->assertForbidden();
});

test('admin can access pembelian create page', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)
        ->get(route('admin.pembelian.create'))
        ->assertOk()
        ->assertSee('Input Faktur Pembelian');
});

test('admin can record a new purchase and stock increases correctly', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::first();

    $initialGudang = $product->stok_gudang;
    $initialTotal = $product->stok;

    $supplier = Supplier::create([
        'nama' => 'PT. Distribusi Sejahtera',
        'telepon' => '081234567890',
    ]);

    Livewire::actingAs($admin)
        ->test(PembelianForm::class)
        ->set('no_faktur', 'PB-TEST-0001')
        ->set('supplier_id', $supplier->id)
        ->set('nama_supplier', $supplier->nama)
        ->set('status_pembayaran', 'lunas')
        ->call('selectProduct', $product->id)
        ->set('items.0.qty', 10)
        ->set('items.0.harga_beli', 15000)
        ->set('items.0.lokasi_tujuan', 'gudang')
        ->set('items.0.update_harga_beli_master', true)
        ->call('simpanFaktur')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.pembelian.index'));

    // Verifikasi faktur tercatat di database
    $purchase = Purchase::where('no_faktur', 'PB-TEST-0001')->first();
    expect($purchase)->not->toBeNull();
    expect($purchase->supplier_id)->toBe($supplier->id);
    expect($purchase->total)->toBe(150000.0);
    expect($purchase->status_pembayaran)->toBe('lunas');

    // Verifikasi stok produk bertambah
    $product->refresh();
    expect($product->stok_gudang)->toBe($initialGudang + 10);
    expect($product->stok)->toBe($initialTotal + 10);
    expect((float) $product->harga_beli)->toBe(15000.0);

    // Verifikasi mutasi stok tercatat
    $movement = StockMovement::where('referensi_id', $purchase->id)
        ->where('jenis', StockMovementType::Pembelian)
        ->first();
    expect($movement)->not->toBeNull();
    expect($movement->jumlah)->toBe(10);
});

test('admin can record purchase with hutang tempo status', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::first();

    Livewire::actingAs($admin)
        ->test(PembelianForm::class)
        ->set('no_faktur', 'PB-TEMPO-0001')
        ->set('nama_supplier', 'Agen Sembako Makmur')
        ->set('status_pembayaran', 'hutang')
        ->set('catatan', 'Jatuh tempo 30 hari')
        ->call('selectProduct', $product->id)
        ->set('items.0.qty', 5)
        ->set('items.0.harga_beli', 20000)
        ->call('simpanFaktur')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.pembelian.index'));

    $purchase = Purchase::where('no_faktur', 'PB-TEMPO-0001')->first();
    expect($purchase)->not->toBeNull();
    expect($purchase->status_pembayaran)->toBe('hutang');
    expect($purchase->total)->toBe(100000.0);
});

test('pembelian form validates empty items', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(PembelianForm::class)
        ->set('no_faktur', 'PB-INVALID-0001')
        ->set('nama_supplier', 'Supplier Tanpa Barang')
        ->call('simpanFaktur')
        ->assertHasErrors(['items']);
});

test('admin can create quick supplier from modal', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(PembelianIndex::class)
        ->call('openSupplierModal')
        ->set('supplierNama', 'CV. Aneka Jaya Abadi')
        ->set('supplierTelepon', '08987654321')
        ->set('supplierAlamat', 'Jl. Pergudangan No. 8')
        ->set('supplierKontak', 'Pak Budi')
        ->call('saveSupplier')
        ->assertHasNoErrors()
        ->assertSet('showSupplierModal', false);

    $supplier = Supplier::where('nama', 'CV. Aneka Jaya Abadi')->first();
    expect($supplier)->not->toBeNull();
    expect($supplier->telepon)->toBe('08987654321');
    expect($supplier->nama_kontak)->toBe('Pak Budi');
});
