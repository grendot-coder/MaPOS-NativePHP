<?php

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Livewire\Admin\ReturIndex;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can process sale return, restore stock, deduct shift cash, and prevent over-returning', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::where('stok_etalase', '>=', 10)->first();
    $stokAwal = $product->stok;

    $shift = CashShift::create([
        'user_id' => $admin->id,
        'nama_petugas' => 'Andi',
        'waktu_buka' => now(),
        'saldo_awal' => 100000,
        'saldo_akhir_sistem' => 100000,
        'status' => 'open',
    ]);

    $sale = Sale::create([
        'no_transaksi' => 'TRX-RET-001',
        'kasir_id' => $admin->id,
        'cash_shift_id' => $shift->id,
        'waktu' => now(),
        'total' => 30000,
        'bayar' => 30000,
        'kembali' => 0,
        'metode_pembayaran' => 'cash',
        'status_pembayaran' => 'lunas',
    ]);

    $saleItem = SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'kode_produk' => $product->kode_produk,
        'nama_produk' => $product->nama,
        'qty' => 3,
        'harga_satuan' => 10000,
        'subtotal' => 30000,
    ]);

    // Kurangi stok seolah sudah terjual
    $product->update([
        'stok_etalase' => $product->stok_etalase - 3,
        'stok' => $stokAwal - 3,
    ]);

    // Retur 2 unit via cash refund
    Livewire::actingAs($admin)
        ->test(ReturIndex::class)
        ->call('selectSale', $sale->id)
        ->set("saleReturnQtys.{$saleItem->id}", 2)
        ->set('saleRefundMethod', 'cash')
        ->set('saleReturnReason', 'Barang penyok')
        ->call('submitSaleReturn')
        ->assertHasNoErrors();

    $product->refresh();
    $shift->refresh();
    $saleReturn = SaleReturn::first();

    expect($saleReturn)->not->toBeNull();
    expect($saleReturn->no_retur)->toStartWith('RTJ-');
    expect($saleReturn->total_refund)->toEqual(20000.0);
    expect($product->stok)->toBe($stokAwal - 1);
    expect($shift->total_pengeluaran)->toEqual(20000.0);

    $movement = StockMovement::where('referensi_id', $saleReturn->id)
        ->where('referensi_type', SaleReturn::class)
        ->first();
    expect($movement)->not->toBeNull();
    expect($movement->jenis)->toBe(StockMovementType::Retur);
    expect($movement->jumlah)->toBe(2);

    // Mencoba retur lagi 2 unit padahal sisa cuma 1 -> harus ditolak
    Livewire::actingAs($admin)
        ->test(ReturIndex::class)
        ->call('selectSale', $sale->id)
        ->set("saleReturnQtys.{$saleItem->id}", 2)
        ->call('submitSaleReturn')
        ->assertHasErrors('saleReturn');
});

test('sale return with potong_piutang reduces customer debt balance', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::where('stok_etalase', '>=', 5)->first();

    $customer = Customer::create([
        'kode_member' => 'MBR-RET1',
        'nama' => 'Pelanggan Retur',
        'limit_kredit' => 100000,
        'piutang_berjalan' => 20000,
    ]);

    $sale = Sale::create([
        'no_transaksi' => 'TRX-RET-BON1',
        'kasir_id' => $admin->id,
        'customer_id' => $customer->id,
        'waktu' => now(),
        'total' => 20000,
        'bayar' => 0,
        'kembali' => 0,
        'metode_pembayaran' => 'tempo',
        'status_pembayaran' => 'piutang',
        'sisa_piutang' => 20000,
    ]);

    $saleItem = SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'kode_produk' => $product->kode_produk,
        'nama_produk' => $product->nama,
        'qty' => 2,
        'harga_satuan' => 10000,
        'subtotal' => 20000,
    ]);

    Livewire::actingAs($admin)
        ->test(ReturIndex::class)
        ->call('selectSale', $sale->id)
        ->set("saleReturnQtys.{$saleItem->id}", 2)
        ->set('saleRefundMethod', 'potong_piutang')
        ->call('submitSaleReturn')
        ->assertHasNoErrors();

    $customer->refresh();
    $sale->refresh();
    expect($customer->piutang_berjalan)->toEqual(0.0);
    expect((float) $sale->sisa_piutang)->toEqual(0.0);
    expect($sale->status_pembayaran)->toBe('lunas');
});

test('admin can process purchase return to supplier, deduct stock, and reduce supplier debt', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $product = Product::where('stok_gudang', '>=', 5)->first();
    $stokAwal = $product->stok;

    $purchase = Purchase::create([
        'no_faktur' => 'PB-RET-001',
        'nama_supplier' => 'Supplier Makmur',
        'user_id' => $admin->id,
        'tanggal' => now(),
        'total' => 50000,
        'status_pembayaran' => 'hutang',
        'dibayar' => 0,
    ]);

    $purchaseItem = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'nama_produk' => $product->nama,
        'qty' => 5,
        'lokasi_tujuan' => 'gudang',
        'harga_beli' => 10000,
        'subtotal' => 50000,
    ]);

    Livewire::actingAs($admin)
        ->test(ReturIndex::class)
        ->call('switchTab', 'pembelian')
        ->call('selectPurchase', $purchase->id)
        ->set("purchaseReturnQtys.{$purchaseItem->id}", 2)
        ->set('purchaseRefundMethod', 'potong_hutang')
        ->set('purchaseReturnReason', 'Dus robek')
        ->call('submitPurchaseReturn')
        ->assertHasNoErrors();

    $product->refresh();
    $purchase->refresh();
    $purchaseReturn = PurchaseReturn::first();

    expect($purchaseReturn)->not->toBeNull();
    expect($purchaseReturn->no_retur)->toStartWith('RTB-');
    expect($purchaseReturn->total_retur)->toEqual(20000.0);
    expect($product->stok)->toBe($stokAwal - 2);
    expect($purchase->dibayar)->toEqual(20000.0);
    expect($purchase->sisa_hutang)->toEqual(30000.0);
});
