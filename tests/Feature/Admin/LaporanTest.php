<?php

use App\Enums\UserRole;
use App\Livewire\Admin\LaporanPenjualan;
use App\Livewire\Admin\LaporanStok;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can access laporan penjualan and laporan stok pages', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)
        ->get(route('admin.laporan.penjualan'))
        ->assertOk()
        ->assertSee('Laporan Penjualan');

    $this->actingAs($admin)
        ->get(route('admin.laporan.stok'))
        ->assertOk()
        ->assertSee('Laporan Stok')
        ->assertSee('Evaluasi Aset');
});

test('kasir cannot access admin laporan pages', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($kasir)
        ->get(route('admin.laporan.penjualan'))
        ->assertForbidden();

    $this->actingAs($kasir)
        ->get(route('admin.laporan.stok'))
        ->assertForbidden();
});

test('laporan penjualan calculates omzet and displays sale receipt modal', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::first();

    // Buat dummy sale
    $sale = Sale::create([
        'no_transaksi' => 'TRX-TEST-0001',
        'kasir_id' => $kasir->id,
        'waktu' => now(),
        'total' => 25000,
        'bayar' => 30000,
        'kembali' => 5000,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'kode_produk' => $product->kode_produk,
        'nama_produk' => $product->nama,
        'qty' => 5,
        'harga_satuan' => 5000,
        'subtotal' => 25000,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(LaporanPenjualan::class)
        ->call('setPeriod', 'today')
        ->assertSee('TRX-TEST-0001')
        ->assertSee('25.000')
        ->assertViewHas('totalOmzet', 25000.0)
        ->assertViewHas('totalTransaksi', 1);

    // Buka modal struk
    $component->call('showReceipt', $sale->id)
        ->assertSet('showReceiptModal', true)
        ->assertSet('selectedSaleId', $sale->id)
        ->assertSee('TRX-TEST-0001');
});

test('laporan stok calculates total modal asset and filters properly', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $component = Livewire::actingAs($admin)
        ->test(LaporanStok::class)
        ->assertViewHas('totalNilaiModal', fn ($val) => $val > 0)
        ->assertViewHas('totalNilaiRetail', fn ($val) => $val > 0);

    // Test filter status
    $component->set('statusFilter', 'aman')
        ->assertHasNoErrors();
});

test('laporan penjualan computes top products, peak hours, and exports csv cleanly', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::first();

    $sale = Sale::create([
        'no_transaksi' => 'TRX-ANLY-001',
        'kasir_id' => $kasir->id,
        'waktu' => now()->setTime(14, 15),
        'total' => 30000,
        'bayar' => 30000,
        'kembali' => 0,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'kode_produk' => $product->kode_produk,
        'nama_produk' => $product->nama,
        'qty' => 6,
        'harga_satuan' => 5000,
        'subtotal' => 30000,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(LaporanPenjualan::class)
        ->call('setPeriod', 'today')
        ->assertViewHas('topProducts', fn ($items) => $items->first()?->total_qty == 6)
        ->assertViewHas('peakHours', fn ($hours) => count($hours) === 1 && $hours[0]['label'] === '14:00');

    $component->call('exportCsv')
        ->assertFileDownloaded('laporan-penjualan-'.now()->format('Y-m-d').'-sd-'.now()->format('Y-m-d').'.csv');

    Livewire::actingAs($admin)
        ->test(LaporanStok::class)
        ->call('exportCsv')
        ->assertFileDownloaded('laporan-stok-aset-'.date('Ymd').'.csv');
});
