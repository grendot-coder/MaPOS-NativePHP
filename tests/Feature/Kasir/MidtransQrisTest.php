<?php

use App\Enums\UserRole;
use App\Livewire\Kasir\PosIndex;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    Config::set('midtrans.server_key', 'SB-Mid-server-TESTKEY123');
    Config::set('midtrans.is_production', false);
    Config::set('midtrans.base_url', 'https://api.sandbox.midtrans.com/v2');
});

test('kasir can generate midtrans qris charge and auto-complete sale upon settlement status', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();
    $product->update(['harga_retail' => 15000, 'diskon' => 0]);

    Http::fake([
        'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
            'status_code' => '201',
            'transaction_status' => 'pending',
            'qr_string' => '000201010212MOCKQRSTRING',
            'actions' => [
                [
                    'name' => 'generate-qr-code',
                    'method' => 'GET',
                    'url' => 'https://api.sandbox.midtrans.com/v2/qris/mock-qr.png',
                ],
            ],
        ], 201),
        'https://api.sandbox.midtrans.com/v2/*/status' => Http::sequence()
            ->push(['status_code' => '200', 'transaction_status' => 'pending'], 200)
            ->push(['status_code' => '200', 'transaction_status' => 'settlement'], 200),
    ]);

    $test = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id)
        ->call('openPayment')
        ->set('paymentMethod', 'qris')
        ->call('generateMidtransQris')
        ->assertSet('midtransStatus', 'pending')
        ->assertSet('midtransQrUrl', 'https://api.sandbox.midtrans.com/v2/qris/mock-qr.png')
        ->assertSet('midtransQrString', '000201010212MOCKQRSTRING');

    // First poll: still pending
    $test->call('checkMidtransStatus')
        ->assertSet('midtransStatus', 'pending')
        ->assertSet('showReceiptModal', false);

    // Second poll: settled -> transaction auto-completes and opens receipt modal
    $test->call('checkMidtransStatus')
        ->assertSet('showPaymentModal', false)
        ->assertSet('showReceiptModal', true)
        ->assertSet('midtransOrderId', null);

    $sale = Sale::latest()->first();
    expect($sale)->not->toBeNull();
    expect($sale->metode_pembayaran)->toBe('qris');
    expect((float) $sale->total)->toEqual(15000.0);
});

test('kasir can cancel pending midtrans qris charge', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();

    Http::fake([
        'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
            'status_code' => '201',
            'transaction_status' => 'pending',
            'qr_string' => '000201010212MOCKQRSTRING',
            'actions' => [
                ['name' => 'generate-qr-code', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/mock-qr.png'],
            ],
        ], 201),
        'https://api.sandbox.midtrans.com/v2/*/cancel' => Http::response([
            'status_code' => '200',
            'transaction_status' => 'cancel',
        ], 200),
    ]);

    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id)
        ->call('openPayment')
        ->set('paymentMethod', 'qris')
        ->call('generateMidtransQris')
        ->assertSet('midtransStatus', 'pending')
        ->call('cancelMidtransQris')
        ->assertSet('midtransOrderId', null)
        ->assertSet('midtransQrUrl', null)
        ->assertSet('midtransStatus', '');
});
