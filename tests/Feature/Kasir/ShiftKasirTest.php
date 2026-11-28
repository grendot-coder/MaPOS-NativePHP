<?php

use App\Enums\UserRole;
use App\Livewire\Kasir\PosIndex;
use App\Models\CashShift;
use App\Models\Pengeluaran;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('kasir sees shift indicator and can open shift with starting cash and officer name', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    // Verify initial state has no active shift
    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->assertSee('Buka Shift Kasir')
        ->set('namaPetugasInput', 'Rina')
        ->set('saldoAwalInput', 150000)
        ->set('catatanShift', 'Shift Pagi Reguler')
        ->call('openShift')
        ->assertSet('showOpenShiftModal', false)
        ->assertSee('150.000')
        ->assertSee('Rina');

    $shift = CashShift::where('user_id', $kasir->id)->where('status', 'open')->first();
    expect($shift)->not->toBeNull();
    expect($shift->nama_petugas)->toEqual('Rina');
    expect((float) $shift->saldo_awal)->toEqual(150000.0);
});

test('kasir must provide officer name when opening a shift', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->set('namaPetugasInput', '')
        ->set('saldoAwalInput', 100000)
        ->call('openShift')
        ->assertHasErrors(['namaPetugasInput' => 'required']);

    expect(CashShift::where('user_id', $kasir->id)->where('status', 'open')->count())->toBe(0);
});

test('kasir cannot open multiple active shifts simultaneously', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->set('namaPetugasInput', 'Rina')
        ->set('saldoAwalInput', 100000)
        ->call('openShift');

    expect(CashShift::where('user_id', $kasir->id)->where('status', 'open')->count())->toBe(1);

    // Attempt to open another shift while one is already active
    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->set('namaPetugasInput', 'Budi')
        ->set('saldoAwalInput', 200000)
        ->call('openShift');

    // Count must remain 1
    expect(CashShift::where('user_id', $kasir->id)->where('status', 'open')->count())->toBe(1);
});

test('sales transaction and shift settlement receipt display shift officer name instead of generic account name', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();

    $shift = CashShift::create([
        'user_id' => $kasir->id,
        'nama_petugas' => 'Siti Aminah',
        'waktu_buka' => now()->subHours(2),
        'saldo_awal' => 100000,
        'saldo_akhir_sistem' => 100000,
        'status' => 'open',
    ]);

    $test = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id)
        ->call('openPayment');

    $grandTotal = $test->get('grandTotal');

    $test->set('nominalBayar', $grandTotal)
        ->call('processTransaction')
        ->assertSet('showReceiptModal', true)
        ->assertSee('Siti Aminah');

    $test->call('closeReceiptModal')
        ->call('confirmCloseShift')
        ->set('saldoFisikInput', 100000 + $grandTotal)
        ->call('closeShift')
        ->assertSet('showShiftReceiptModal', true)
        ->assertSee('Siti Aminah');
});

test('sales transaction during active shift is linked to that shift', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();

    // Open shift
    $shift = CashShift::create([
        'user_id' => $kasir->id,
        'waktu_buka' => now(),
        'saldo_awal' => 100000,
        'saldo_akhir_sistem' => 100000,
        'status' => 'open',
    ]);

    $component = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->assertSet('activeShiftId', $shift->id)
        ->call('selectProduct', $product->id)
        ->call('openPayment');

    $grandTotal = $component->get('grandTotal');

    $component->set('nominalBayar', $grandTotal + 10000)
        ->call('processTransaction')
        ->assertSet('showReceiptModal', true);

    $lastSale = Sale::latest()->first();
    expect($lastSale->cash_shift_id)->toEqual($shift->id);

    // Livewire refresh: verify shift total sales and live drawer balance
    $shift->refresh();
    expect($shift->total_penjualan)->toEqual((float) $grandTotal);
    expect($shift->saldo_saat_ini)->toEqual(100000.0 + (float) $grandTotal);
});

test('pengeluaran during active shift is linked and deducts from live drawer balance', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    // Open shift with 200,000
    $shift = CashShift::create([
        'user_id' => $kasir->id,
        'waktu_buka' => now(),
        'saldo_awal' => 200000,
        'saldo_akhir_sistem' => 200000,
        'status' => 'open',
    ]);

    // Record expense of 25,000
    Pengeluaran::create([
        'no_referensi' => 'EXP-TEST-0001',
        'kategori' => 'operasional',
        'nominal' => 25000,
        'keterangan' => 'Beli kantong plastik kasir',
        'user_id' => $kasir->id,
        'cash_shift_id' => $shift->id,
        'waktu' => now(),
    ]);

    $shift->refresh();
    expect($shift->total_pengeluaran)->toEqual(25000.0);
    expect($shift->saldo_saat_ini)->toEqual(175000.0);

    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->assertSee('175.000');
});

test('kasir can close shift with physical count, computes selisih, and shows settlement receipt', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    // Open shift with 100,000
    $shift = CashShift::create([
        'user_id' => $kasir->id,
        'waktu_buka' => now()->subHours(8),
        'saldo_awal' => 100000,
        'saldo_akhir_sistem' => 100000,
        'status' => 'open',
    ]);

    $test = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('confirmCloseShift')
        ->assertSet('showCloseShiftModal', true)
        ->assertSet('saldoFisikInput', 100000.0);

    // Kasir inputs physical cash count of 105,000 (+5,000 surplus)
    $test->set('saldoFisikInput', 105000)
        ->set('catatanShift', 'Ada kembalian permen lebih')
        ->call('closeShift')
        ->assertSet('showCloseShiftModal', false)
        ->assertSet('showShiftReceiptModal', true)
        ->assertSet('activeShiftId', null);

    $shift->refresh();
    expect($shift->status)->toEqual('closed');
    expect($shift->waktu_tutup)->not->toBeNull();
    expect((float) $shift->saldo_akhir_sistem)->toEqual(100000.0);
    expect((float) $shift->saldo_akhir_fisik)->toEqual(105000.0);
    expect((float) $shift->selisih)->toEqual(5000.0);
    expect($shift->status_selisih)->toEqual('lebih');

    // Close the receipt modal
    $test->call('closeShiftReceiptModal')
        ->assertSet('showShiftReceiptModal', false)
        ->assertSet('lastClosedShiftId', null);
});
