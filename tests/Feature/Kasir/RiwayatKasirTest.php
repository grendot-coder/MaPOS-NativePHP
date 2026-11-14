<?php

use App\Enums\UserRole;
use App\Livewire\Kasir\RiwayatPenjualan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('kasir can access riwayat transaksi page', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($kasir)
        ->get(route('kasir.riwayat'))
        ->assertOk()
        ->assertSee('Riwayat Transaksi Saya');
});

test('kasir only sees their own transactions', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::first();

    // Buat transaksi milik kasir
    $saleKasir = Sale::create([
        'no_transaksi' => 'TRX-KASIR-0001',
        'kasir_id' => $kasir->id,
        'waktu' => now(),
        'total' => 15000,
        'bayar' => 20000,
        'kembali' => 5000,
    ]);

    // Buat transaksi milik admin
    $saleAdmin = Sale::create([
        'no_transaksi' => 'TRX-ADMIN-9999',
        'kasir_id' => $admin->id,
        'waktu' => now(),
        'total' => 50000,
        'bayar' => 50000,
        'kembali' => 0,
    ]);

    Livewire::actingAs($kasir)
        ->test(RiwayatPenjualan::class)
        ->set('filterDate', now()->format('Y-m-d'))
        ->assertSee('TRX-KASIR-0001')
        ->assertDontSee('TRX-ADMIN-9999');
});

test('kasir can open receipt modal to reprint receipt', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::first();

    $sale = Sale::create([
        'no_transaksi' => 'TRX-REPRINT-01',
        'kasir_id' => $kasir->id,
        'waktu' => now(),
        'total' => 10000,
        'bayar' => 10000,
        'kembali' => 0,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'kode_produk' => $product->kode_produk,
        'nama_produk' => $product->nama,
        'qty' => 1,
        'harga_satuan' => 10000,
        'subtotal' => 10000,
    ]);

    Livewire::actingAs($kasir)
        ->test(RiwayatPenjualan::class)
        ->call('showReceipt', $sale->id)
        ->assertSet('showReceiptModal', true)
        ->assertSet('selectedSaleId', $sale->id)
        ->assertSee('TRX-REPRINT-01')
        ->assertSee('SALINAN / REPRINT');
});
