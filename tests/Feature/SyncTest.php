<?php

namespace Tests\Feature;

use App\Models\Golongan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiToken = 'test-sync-token-123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['sync.api_token' => $this->apiToken]);
        config(['sync.server_url' => 'http://127.0.0.1:8080']);
    }

    public function test_sync_ping_succeeds_with_valid_bearer_token(): void
    {
        $response = $this->withToken($this->apiToken)
            ->getJson('/api/sync/ping');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
            ])
            ->assertJsonStructure(['status', 'message', 'server_time', 'app_name']);
    }

    public function test_sync_ping_fails_with_invalid_token(): void
    {
        $response = $this->withToken('wrong-token')
            ->getJson('/api/sync/ping');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_sync_sales_endpoint_persists_sales_and_items(): void
    {
        $user = User::factory()->create();
        $golongan = Golongan::create(['nama_golongan' => 'Minuman', 'kode_golongan' => '01']);
        $product = Product::create([
            'kode_produk' => 'PRD-001',
            'nama' => 'Teh Botol',
            'golongan_id' => $golongan->id,
            'satuan' => 'pcs',
            'harga_beli' => 3000,
            'harga_retail' => 5000,
            'stok' => 50,
        ]);

        $saleId = (string) Str::uuid();
        $itemId = (string) Str::uuid();

        $payload = [
            'sales' => [
                [
                    'id' => $saleId,
                    'no_transaksi' => 'TRX-TEST-001',
                    'kasir_id' => $user->id,
                    'waktu' => now()->toIso8601String(),
                    'total' => 10000,
                    'bayar' => 10000,
                    'kembali' => 0,
                    'items' => [
                        [
                            'id' => $itemId,
                            'product_id' => $product->id,
                            'kode_produk' => 'PRD-001',
                            'nama_produk' => 'Teh Botol',
                            'qty' => 2,
                            'harga_satuan' => 5000,
                            'subtotal' => 10000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->withToken($this->apiToken)
            ->postJson('/api/sync/sales', $payload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'synced_ids' => [$saleId],
            ]);

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'no_transaksi' => 'TRX-TEST-001',
            'total' => 10000,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'id' => $itemId,
            'sale_id' => $saleId,
            'qty' => 2,
        ]);
    }

    public function test_get_catalog_returns_products(): void
    {
        $golongan = Golongan::create(['nama_golongan' => 'Makanan', 'kode_golongan' => '02']);
        Product::create([
            'kode_produk' => 'PRD-002',
            'nama' => 'Roti Bakar',
            'golongan_id' => $golongan->id,
            'satuan' => 'pcs',
            'harga_beli' => 8000,
            'harga_retail' => 15000,
            'stok' => 20,
        ]);

        $response = $this->withToken($this->apiToken)
            ->getJson('/api/sync/catalog');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonFragment(['kode_produk' => 'PRD-002', 'nama' => 'Roti Bakar']);
    }

    public function test_sync_service_push_sales_marks_sales_synced(): void
    {
        $user = User::factory()->create();
        $golongan = Golongan::create(['nama_golongan' => 'Snack', 'kode_golongan' => '03']);
        $product = Product::create([
            'kode_produk' => 'PRD-003',
            'nama' => 'Keripik',
            'golongan_id' => $golongan->id,
            'satuan' => 'pcs',
            'harga_beli' => 2000,
            'harga_retail' => 4000,
            'stok' => 30,
        ]);

        $sale = Sale::create([
            'no_transaksi' => 'TRX-LOCAL-001',
            'kasir_id' => $user->id,
            'total' => 8000,
            'bayar' => 10000,
            'kembali' => 2000,
            'synced_at' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'kode_produk' => 'PRD-003',
            'nama_produk' => 'Keripik',
            'qty' => 2,
            'harga_satuan' => 4000,
            'subtotal' => 8000,
        ]);

        $this->assertNull($sale->fresh()->synced_at);

        Http::fake([
            'http://127.0.0.1:8080/api/sync/sales' => Http::response([
                'status' => 'success',
                'synced_ids' => [$sale->id],
            ], 200),
        ]);

        $service = new SyncService;
        $result = $service->pushSales();

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
        $this->assertNotNull($sale->fresh()->synced_at);
    }
}
