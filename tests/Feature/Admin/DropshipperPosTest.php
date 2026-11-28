<?php

use App\Enums\UserRole;
use App\Livewire\Admin\DropshipperIndex;
use App\Livewire\Admin\LaporanPenjualan;
use App\Livewire\Kasir\PosIndex;
use App\Models\Dropshipper;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can manage dropshippers and kasir is forbidden', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($admin)->get(route('admin.dropshipper.index'))->assertOk()->assertSee('Master Dropshipper');
    $this->actingAs($kasir)->get(route('admin.dropshipper.index'))->assertForbidden();

    Livewire::actingAs($admin)
        ->test(DropshipperIndex::class)
        ->call('create')
        ->set('nama', 'Reseller Berkah')
        ->set('telepon', '081999888777')
        ->set('diskon_khusus', 10)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $dropshipper = Dropshipper::where('nama', 'Reseller Berkah')->first();
    expect($dropshipper)->not->toBeNull();
    expect((float) $dropshipper->diskon_khusus)->toBe(10.0);
});

test('kasir can process dropship sale with reseller discount and shipping label', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->where('harga_retail', '>', 0)->first();

    $dropshipper = Dropshipper::create([
        'kode_dropshipper' => 'DRP-TEST1',
        'nama' => 'Toko Maju Jaya Dropship',
        'telepon' => '081555666777',
        'alamat' => 'Jl. Merdeka No. 1',
        'diskon_khusus' => 10,
        'status_aktif' => true,
    ]);

    $expectedGross = (float) $product->harga_retail * 2;
    $expectedDiskonDropship = (float) round($expectedGross * 0.10, 0);
    $expectedGrandTotal = max(0, $expectedGross - $expectedDiskonDropship);

    $component = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id)
        ->call('incrementQty', $product->id)
        ->set('isDropship', true)
        ->set('selectedDropshipperId', $dropshipper->id)
        ->set('dropshipPenerimaNama', 'Andi Pembeli')
        ->set('dropshipPenerimaTelepon', '089876543210')
        ->set('dropshipPenerimaAlamat', 'Jl. Sudirman No. 45, Bandung')
        ->assertSet('diskonDropship', $expectedDiskonDropship)
        ->assertSet('grandTotal', $expectedGrandTotal)
        ->call('openPayment')
        ->set('nominalBayar', $expectedGrandTotal)
        ->call('processTransaction')
        ->assertSet('showReceiptModal', true);

    $sale = Sale::latest('created_at')->first();
    expect($sale)->not->toBeNull();
    expect($sale->is_dropship)->toBeTrue();
    expect($sale->dropshipper_id)->toBe($dropshipper->id);
    expect($sale->dropship_penerima_nama)->toBe('Andi Pembeli');
    expect((float) $sale->total)->toBe($expectedGrandTotal);

    $component->assertSee('LABEL PENGIRIMAN DROPSHIP')
        ->assertSee('Toko Maju Jaya Dropship')
        ->assertSee('Andi Pembeli')
        ->assertSee('Jl. Sudirman No. 45, Bandung');

    // Deleting a dropshipper with sales deactivates instead of deleting
    $admin = User::where('role', UserRole::Admin)->first();
    Livewire::actingAs($admin)
        ->test(DropshipperIndex::class)
        ->call('delete', $dropshipper->id);

    $dropshipper->refresh();
    expect($dropshipper->status_aktif)->toBeFalse();
});

test('dropship checkout requires dropshipper and recipient name', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 2)->first();

    $initialSaleCount = Sale::count();

    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id)
        ->set('isDropship', true)
        ->call('openPayment')
        ->set('nominalBayar', (float) $product->harga_retail)
        ->call('processTransaction')
        ->assertSet('showReceiptModal', false);

    expect(Sale::count())->toBe($initialSaleCount);
});

test('admin laporan penjualan displays dropship badge and shipping label', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $dropshipper = Dropshipper::create([
        'kode_dropshipper' => 'DRP-RPT1',
        'nama' => 'Mitra Kirim',
        'telepon' => '08111222333',
        'diskon_khusus' => 0,
        'status_aktif' => true,
    ]);

    $sale = Sale::create([
        'no_transaksi' => 'INV-DRP-0001',
        'kasir_id' => $admin->id,
        'waktu' => now(),
        'total' => 50000,
        'bayar' => 50000,
        'kembali' => 0,
        'metode_pembayaran' => 'cash',
        'status_pembayaran' => 'lunas',
        'sisa_piutang' => 0,
        'is_dropship' => true,
        'dropshipper_id' => $dropshipper->id,
        'dropship_penerima_nama' => 'Budi Penerima',
        'dropship_penerima_telepon' => '08222333444',
        'dropship_penerima_alamat' => 'Surabaya',
    ]);

    Livewire::actingAs($admin)
        ->test(LaporanPenjualan::class)
        ->assertSee('Dropship: Mitra Kirim')
        ->call('showReceipt', $sale->id)
        ->assertSee('Cetak Label Dropship')
        ->assertSee('Budi Penerima');
});
