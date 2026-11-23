<?php

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Livewire\Kasir\PosIndex;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SaleService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('kasir can access pos page', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($kasir)
        ->get(route('kasir.pos'))
        ->assertOk()
        ->assertSee('Point of Sale (Kasir)')
        ->assertSee('Pemindai Barcode')
        ->assertSee('Keranjang Belanja');
});

test('kasir can scan barcode to add product to cart', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();

    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->set('barcodeInput', $product->kode_produk)
        ->call('scanBarcode')
        ->assertSet('barcodeInput', '')
        ->assertCount('cart', 1)
        ->assertSee($product->nama);
});

test('kasir can scan barcode with upc-a fallback', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    // Test product with 12-digit or 13-digit
    $product = Product::where('stok', '>', 5)->first();

    // If product code is 13-digit starting with 0, or prepend 0 to match fallback
    $upcCode = strlen($product->kode_produk) === 12 ? '0'.$product->kode_produk : $product->kode_produk;

    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->set('barcodeInput', $upcCode)
        ->call('scanBarcode')
        ->assertCount('cart', 1);
});

test('kasir can update qty and cannot exceed available stock', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();

    $test = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id)
        ->assertCount('cart', 1);

    // Increment
    $test->call('incrementQty', $product->id);
    expect($test->get('cart')[$product->id]['qty'])->toBe(2);

    // Try setting qty beyond stock
    $test->call('updateQty', $product->id, $product->stok + 999);
    expect($test->get('cart')[$product->id]['qty'])->toBe($product->stok);

    // Decrement
    $test->call('decrementQty', $product->id);
    expect($test->get('cart')[$product->id]['qty'])->toBe($product->stok - 1);
});

test('kasir can process sale and stock is properly deducted and logged', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();

    $initialStock = $product->stok;
    $initialEtalase = $product->stok_etalase;

    $test = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id)
        ->call('updateQty', $product->id, 2)
        ->call('openPayment');

    $grandTotal = $test->get('grandTotal');
    expect($grandTotal)->toBeGreaterThan(0);

    // Bayar dengan uang lebih
    $bayar = $grandTotal + 10000;
    $test->set('nominalBayar', $bayar)
        ->call('processTransaction');

    // Keranjang harus sudah kosong dan modal struk muncul
    $test->assertSet('cart', [])
        ->assertSet('showReceiptModal', true)
        ->assertSee('MAPOS MINIMARKET')
        ->assertSee('Cetak Struk Sekarang')
        ->assertSee('Thermal 80mm')
        ->assertSee('receipt-print-area')
        ->assertSee('Total Item / Qty:');

    $lastSaleId = $test->get('lastSaleId');
    expect($lastSaleId)->not->toBeNull();

    // Verifikasi Sale di DB
    $sale = Sale::with('items')->find($lastSaleId);
    expect($sale)->not->toBeNull();
    expect($sale->no_transaksi)->toStartWith('TRX-');
    expect($sale->total)->toEqual($grandTotal);
    expect($sale->bayar)->toEqual($bayar);
    expect($sale->kembali)->toEqual(10000.0);
    expect($sale->items)->toHaveCount(1);
    expect($sale->items[0]->qty)->toBe(2);

    // Verifikasi stok produk berkurang 2
    $product->refresh();
    expect($product->stok)->toBe($initialStock - 2);

    // Verifikasi pencatatan StockMovement
    $movement = StockMovement::where('referensi_id', $sale->id)
        ->where('referensi_type', Sale::class)
        ->first();

    expect($movement)->not->toBeNull();
    expect($movement->jenis)->toBe(StockMovementType::Penjualan);
    expect($movement->jumlah)->toBe(-2);
    expect($movement->saldo_sesudah)->toBe($initialStock - 2);
});

test('kasir transaction rejected when payment is insufficient', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $product = Product::where('stok', '>', 5)->first();

    $test = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id)
        ->call('openPayment');

    $grandTotal = $test->get('grandTotal');

    // Coba bayar kurang dari total
    $test->set('nominalBayar', $grandTotal - 500)
        ->call('processTransaction')
        ->assertSet('showReceiptModal', false);

    expect(count($test->get('cart')))->toBe(1);
});

test('sale service generates sequential and length-aware transaction numbers', function () {
    $saleService = app(SaleService::class);
    $kasir = User::where('role', UserRole::Kasir)->first();
    $todayPrefix = 'TRX-'.now()->format('Ymd').'-';

    // Create a high-number sale to test length-aware sorting (past 9999)
    Sale::create([
        'no_transaksi' => $todayPrefix.'9999',
        'kasir_id' => $kasir->id,
        'waktu' => now(),
        'total' => 10000,
        'bayar' => 10000,
        'kembali' => 0,
    ]);

    expect($saleService->generateTransactionNumber())->toBe($todayPrefix.'10000');

    Sale::create([
        'no_transaksi' => $todayPrefix.'10000',
        'kasir_id' => $kasir->id,
        'waktu' => now(),
        'total' => 10000,
        'bayar' => 10000,
        'kembali' => 0,
    ]);

    expect($saleService->generateTransactionNumber())->toBe($todayPrefix.'10001');
});
