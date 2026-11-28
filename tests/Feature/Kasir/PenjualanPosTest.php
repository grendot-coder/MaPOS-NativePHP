<?php

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Livewire\Kasir\PosIndex;
use App\Models\Golongan;
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

test('kasir automatically switches unit price to wholesale tiers based on product min qty and respects promo dates', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $golongan = Golongan::first();

    $product = Product::create([
        'golongan_id' => $golongan->id,
        'kode_produk' => '8990000111222',
        'nama' => 'Minyak Goreng Grosir Test',
        'satuan' => 'pcs',
        'harga_beli' => 10000,
        'harga_retail' => 15000,
        'harga_grosir_1' => 13500,
        'min_qty_grosir_1' => 5,
        'harga_grosir_2' => 12000,
        'min_qty_grosir_2' => 12,
        'diskon' => 10,
        'promo_mulai' => now()->subDay()->format('Y-m-d'),
        'promo_sampai' => now()->addDay()->format('Y-m-d'),
        'stok_etalase' => 30,
        'stok_gudang' => 0,
        'stok' => 30,
        'min_stok' => 5,
    ]);

    $test = Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('selectProduct', $product->id);

    // Qty 1 -> Retail 15,000 - 10% promo = 13,500
    expect($test->get('cart')[$product->id]['harga_satuan'])->toEqual(15000.0);
    expect($test->get('cart')[$product->id]['tier_harga'])->toBeNull();
    expect($test->get('grandTotal'))->toEqual(13500.0);

    // Qty 5 -> Grosir 1 (13,500) - 10% promo = 12,150 x 5 = 60,750
    $test->call('updateQty', $product->id, 5);
    expect($test->get('cart')[$product->id]['harga_satuan'])->toEqual(13500.0);
    expect($test->get('cart')[$product->id]['tier_harga'])->toBe('Grosir 1');
    expect($test->get('grandTotal'))->toEqual(60750.0);

    // Qty 12 -> Grosir 2 (12,000) - 10% promo = 10,800 x 12 = 129,600
    $test->call('updateQty', $product->id, 12);
    expect($test->get('cart')[$product->id]['harga_satuan'])->toEqual(12000.0);
    expect($test->get('cart')[$product->id]['tier_harga'])->toBe('Grosir 2');
    expect($test->get('grandTotal'))->toEqual(129600.0);

    // Drop back to Qty 3 -> reverts to Retail
    $test->call('updateQty', $product->id, 3);
    expect($test->get('cart')[$product->id]['harga_satuan'])->toEqual(15000.0);
    expect($test->get('cart')[$product->id]['tier_harga'])->toBeNull();

    // Expired promo -> discount becomes 0%
    $product->update([
        'promo_mulai' => now()->subDays(10)->format('Y-m-d'),
        'promo_sampai' => now()->subDays(2)->format('Y-m-d'),
    ]);
    $test->call('updateQty', $product->id, 1);
    expect($test->get('cart')[$product->id]['diskon'])->toEqual(0.0);
    expect($test->get('grandTotal'))->toEqual(15000.0);
});
