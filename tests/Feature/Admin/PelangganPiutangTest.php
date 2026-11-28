<?php

use App\Enums\UserRole;
use App\Livewire\Admin\PelangganIndex;
use App\Livewire\Admin\PembelianIndex;
use App\Livewire\Kasir\PosIndex;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can create customer member with credit limit', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(PelangganIndex::class)
        ->call('openCreateModal')
        ->set('nama', 'Warung Bu Sari')
        ->set('telepon', '081234567890')
        ->set('limit_kredit', 500000)
        ->call('saveCustomer')
        ->assertHasNoErrors();

    $customer = Customer::where('nama', 'Warung Bu Sari')->first();
    expect($customer)->not->toBeNull();
    expect($customer->kode_member)->toStartWith('MBR-');
    expect($customer->limit_kredit)->toEqual(500000.0);
});

test('member earns points on qris sale, can redeem points, and qris does not inflate cash drawer', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();
    $product->update(['harga_retail' => 25000, 'diskon' => 0]);

    $customer = Customer::create([
        'kode_member' => 'MBR-0001',
        'nama' => 'Budi Santoso',
        'poin' => 50,
        'limit_kredit' => 0,
    ]);

    $shift = CashShift::create([
        'user_id' => $kasir->id,
        'nama_petugas' => 'Budi Kasir',
        'waktu_buka' => now(),
        'saldo_awal' => 100000,
        'saldo_akhir_sistem' => 100000,
        'status' => 'open',
    ]);

    // 1. Bayar via QRIS (Rp 25.000 -> dapat 2 poin, laci kasir tetap 100.000)
    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectCustomer', $customer->id)
        ->call('selectProduct', $product->id)
        ->call('openPayment')
        ->set('paymentMethod', 'qris')
        ->call('processTransaction')
        ->assertSet('showReceiptModal', true);

    $customer->refresh();
    $shift->refresh();
    $sale = Sale::latest()->first();

    expect($sale->metode_pembayaran)->toBe('qris');
    expect($sale->poin_didapat)->toBe(2);
    expect($customer->poin)->toBe(52);
    expect($shift->total_penjualan)->toEqual(0.0);
    expect($shift->saldo_saat_ini)->toEqual(100000.0);

    // 2. Transaksi kedua pakai tukar poin (52 poin = Rp 5.200 potongan)
    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectCustomer', $customer->id)
        ->call('selectProduct', $product->id)
        ->set('usePoints', true)
        ->assertSet('poinDipakai', 52)
        ->assertSet('potonganPoin', 5200.0)
        ->assertSet('grandTotal', 19800.0)
        ->call('openPayment')
        ->set('nominalBayar', 20000)
        ->call('processTransaction')
        ->assertSet('showReceiptModal', true);

    $customer->refresh();
    // 52 used, +1 earned from Rp 19.800
    expect($customer->poin)->toBe(1);
});

test('member can buy on tempo bon within credit limit and admin can record installment payment', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();
    $product->update(['harga_retail' => 40000, 'diskon' => 0]);

    $customer = Customer::create([
        'kode_member' => 'MBR-0002',
        'nama' => 'Toko Jaya',
        'limit_kredit' => 50000,
    ]);

    // Bon Rp 40.000 berhasil (di bawah limit Rp 50.000)
    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectCustomer', $customer->id)
        ->call('selectProduct', $product->id)
        ->call('openPayment')
        ->set('paymentMethod', 'tempo')
        ->call('processTransaction')
        ->assertSet('showReceiptModal', true);

    $customer->refresh();
    $sale = Sale::latest()->first();
    expect($sale->status_pembayaran)->toBe('piutang');
    expect((float) $sale->sisa_piutang)->toEqual(40000.0);
    expect($customer->piutang_berjalan)->toEqual(40000.0);

    // Bon kedua Rp 40.000 ditolak karena melebihi limit Rp 50.000 (sisa limit tinggal Rp 10.000)
    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectCustomer', $customer->id)
        ->call('selectProduct', $product->id)
        ->call('openPayment')
        ->set('paymentMethod', 'tempo')
        ->call('processTransaction')
        ->assertSet('showReceiptModal', false);

    // Admin catat cicilan Rp 15.000 lalu pelunasan Rp 25.000
    Livewire::actingAs($admin)
        ->test(PelangganIndex::class)
        ->call('openPaymentModal', $customer->id)
        ->set('paymentAmount', 15000)
        ->set('paymentMethod', 'transfer')
        ->call('savePiutangPayment')
        ->assertHasNoErrors();

    $customer->refresh();
    $sale->refresh();
    expect($customer->piutang_berjalan)->toEqual(25000.0);
    expect((float) $sale->sisa_piutang)->toEqual(25000.0);
    expect($sale->status_pembayaran)->toBe('piutang');

    Livewire::actingAs($admin)
        ->test(PelangganIndex::class)
        ->call('openPaymentModal', $customer->id)
        ->set('paymentAmount', 25000)
        ->call('savePiutangPayment')
        ->assertHasNoErrors();

    $customer->refresh();
    $sale->refresh();
    expect($customer->piutang_berjalan)->toEqual(0.0);
    expect((float) $sale->sisa_piutang)->toEqual(0.0);
    expect($sale->status_pembayaran)->toBe('lunas');
});

test('admin can record partial debt installment and full settlement on supplier purchase', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $purchase = Purchase::create([
        'no_faktur' => 'PB-DEBT-001',
        'nama_supplier' => 'Agen Beras',
        'user_id' => $admin->id,
        'tanggal' => now(),
        'total' => 100000,
        'status_pembayaran' => 'hutang',
        'jatuh_tempo' => now()->addDays(14),
        'dibayar' => 0,
    ]);

    Livewire::actingAs($admin)
        ->test(PembelianIndex::class)
        ->call('openDebtModal', $purchase->id)
        ->set('debtPaymentAmount', 40000)
        ->set('debtPaymentMethod', 'transfer')
        ->call('saveDebtPayment')
        ->assertHasNoErrors();

    $purchase->refresh();
    expect($purchase->dibayar)->toEqual(40000.0);
    expect($purchase->sisa_hutang)->toEqual(60000.0);
    expect($purchase->status_pembayaran)->toBe('hutang');

    Livewire::actingAs($admin)
        ->test(PembelianIndex::class)
        ->call('markAsPaid', $purchase->id);

    $purchase->refresh();
    expect($purchase->dibayar)->toEqual(100000.0);
    expect($purchase->sisa_hutang)->toEqual(0.0);
    expect($purchase->status_pembayaran)->toBe('lunas');
});
