<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\SyncIndex;
use App\Models\CashShift;
use App\Models\Dropshipper;
use App\Models\Golongan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
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

    public function test_sync_sales_accepts_uuid_shift_and_dropship_and_deducts_stock_once(): void
    {
        $user = User::factory()->create();
        $golongan = Golongan::create(['nama_golongan' => 'Minuman', 'kode_golongan' => '01']);
        $product = Product::create([
            'kode_produk' => 'PRD-UUID-1',
            'nama' => 'Kopi Susu',
            'golongan_id' => $golongan->id,
            'satuan' => 'pcs',
            'harga_beli' => 4000,
            'harga_retail' => 8000,
            'stok_etalase' => 15,
            'stok_gudang' => 5,
            'stok' => 20,
        ]);

        $shift = CashShift::create([
            'user_id' => $user->id,
            'nama_petugas' => 'Kasir Test',
            'waktu_buka' => now(),
            'saldo_awal' => 100000,
            'saldo_akhir_sistem' => 100000,
            'status' => 'open',
        ]);

        $dropshipper = Dropshipper::create([
            'kode_dropshipper' => 'DRP-SYNC-1',
            'nama' => 'Reseller Sync',
            'telepon' => '08123456789',
            'diskon_khusus' => 0,
            'status_aktif' => true,
        ]);

        $saleId = (string) Str::uuid();
        $itemId = (string) Str::uuid();

        $payload = [
            'sales' => [
                [
                    'id' => $saleId,
                    'no_transaksi' => 'TRX-SYNC-UUID',
                    'kasir_id' => $user->id,
                    'cash_shift_id' => $shift->id,
                    'waktu' => now()->toIso8601String(),
                    'total' => 24000,
                    'bayar' => 24000,
                    'kembali' => 0,
                    'metode_pembayaran' => 'qris',
                    'status_pembayaran' => 'lunas',
                    'is_dropship' => true,
                    'dropshipper_id' => $dropshipper->id,
                    'dropship_penerima_nama' => 'Penerima Sync',
                    'items' => [
                        [
                            'id' => $itemId,
                            'product_id' => $product->id,
                            'kode_produk' => 'PRD-UUID-1',
                            'nama_produk' => 'Kopi Susu',
                            'qty' => 3,
                            'harga_satuan' => 8000,
                            'subtotal' => 24000,
                        ],
                    ],
                ],
            ],
        ];

        $this->withToken($this->apiToken)->postJson('/api/sync/sales', $payload)->assertOk();

        $product->refresh();
        $this->assertEquals(17, $product->stok);
        $this->assertEquals(12, $product->stok_etalase);
        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'cash_shift_id' => $shift->id,
            'dropshipper_id' => $dropshipper->id,
            'metode_pembayaran' => 'qris',
            'is_dropship' => 1,
        ]);
        $this->assertEquals(1, StockMovement::where('referensi_id', $saleId)->count());

        // Re-sending the same sale must be idempotent and NOT deduct stock twice
        $this->withToken($this->apiToken)->postJson('/api/sync/sales', $payload)->assertOk();
        $this->assertEquals(17, $product->fresh()->stok);
        $this->assertEquals(1, StockMovement::where('referensi_id', $saleId)->count());
    }

    public function test_sync_batch_and_catalog_cover_new_modules(): void
    {
        $user = User::factory()->create();
        $jabatanId = (string) Str::uuid();
        $empId = (string) Str::uuid();
        $custId = (string) Str::uuid();
        $supId = (string) Str::uuid();
        $payrollId = (string) Str::uuid();
        $expenseId = (string) Str::uuid();

        $batchPayload = [
            'jabatans' => [[
                'id' => $jabatanId,
                'kode_jabatan' => 'JBT-S1',
                'nama_jabatan' => 'Kasir',
                'gaji_pokok_default' => 2000000,
                'tunjangan_default' => 100000,
            ]],
            'employees' => [[
                'id' => $empId,
                'kode_karyawan' => 'EMP-S1',
                'jabatan_id' => $jabatanId,
                'user_id' => $user->id,
                'nama' => 'Siti Sync',
                'gaji_pokok' => 2000000,
                'tunjangan' => 100000,
                'status_aktif' => true,
            ]],
            'customers' => [[
                'id' => $custId,
                'kode_member' => 'MBR-S1',
                'nama' => 'Pelanggan Sync',
                'poin' => 50,
                'limit_kredit' => 500000,
                'piutang_berjalan' => 0,
            ]],
            'suppliers' => [[
                'id' => $supId,
                'nama' => 'Supplier Sync',
            ]],
            'payrolls' => [[
                'id' => $payrollId,
                'no_slip' => 'SLIP-202611-0001',
                'employee_id' => $empId,
                'user_id' => $user->id,
                'periode_bulan' => '2026-11',
                'gaji_pokok' => 2000000,
                'total_diterima' => 2100000,
                'status' => 'draft',
            ]],
            'pengeluarans' => [[
                'id' => $expenseId,
                'no_referensi' => 'KSB-S1',
                'kategori' => 'kasbon',
                'nama_karyawan' => 'Siti Sync',
                'employee_id' => $empId,
                'payroll_id' => $payrollId,
                'nominal' => 50000,
                'keterangan' => 'Kasbon sync',
                'user_id' => $user->id,
                'waktu' => now()->toIso8601String(),
            ]],
        ];

        $this->withToken($this->apiToken)
            ->postJson('/api/sync/batch', $batchPayload)
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('employees', ['id' => $empId, 'nama' => 'Siti Sync']);
        $this->assertDatabaseHas('customers', ['id' => $custId, 'poin' => 50]);
        $this->assertDatabaseHas('payrolls', ['id' => $payrollId, 'no_slip' => 'SLIP-202611-0001']);
        $this->assertDatabaseHas('pengeluarans', ['id' => $expenseId, 'payroll_id' => $payrollId]);

        $this->withToken($this->apiToken)
            ->getJson('/api/sync/catalog')
            ->assertOk()
            ->assertJsonStructure(['golongans', 'products', 'customers', 'dropshippers', 'suppliers', 'jabatans', 'employees'])
            ->assertJsonFragment(['kode_member' => 'MBR-S1'])
            ->assertJsonFragment(['kode_karyawan' => 'EMP-S1']);
    }

    public function test_pull_catalog_does_not_overwrite_stock_when_unsynced_sales_exist(): void
    {
        $user = User::factory()->create();
        $golongan = Golongan::create(['nama_golongan' => 'Snack', 'kode_golongan' => '03']);
        $product = Product::create([
            'kode_produk' => 'PRD-LOCK-1',
            'nama' => 'Wafer',
            'golongan_id' => $golongan->id,
            'satuan' => 'pcs',
            'harga_beli' => 2000,
            'harga_retail' => 4000,
            'stok_etalase' => 8,
            'stok_gudang' => 0,
            'stok' => 8,
        ]);

        $sale = Sale::create([
            'no_transaksi' => 'TRX-PENDING-1',
            'kasir_id' => $user->id,
            'waktu' => now(),
            'total' => 8000,
            'bayar' => 8000,
            'kembali' => 0,
            'synced_at' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'kode_produk' => 'PRD-LOCK-1',
            'nama_produk' => 'Wafer',
            'qty' => 2,
            'harga_satuan' => 4000,
            'subtotal' => 8000,
        ]);

        Http::fake([
            'http://127.0.0.1:8080/api/sync/catalog' => Http::response([
                'status' => 'success',
                'golongans' => [],
                'products' => [[
                    'id' => $product->id,
                    'kode_produk' => 'PRD-LOCK-1',
                    'nama' => 'Wafer',
                    'golongan_id' => $golongan->id,
                    'satuan' => 'pcs',
                    'harga_beli' => 2200,
                    'harga_retail' => 4500,
                    'min_qty_grosir_1' => 5,
                    'stok_etalase' => 10,
                    'stok_gudang' => 0,
                    'stok' => 10,
                ]],
            ], 200),
        ]);

        $result = (new SyncService)->pullCatalog();

        $this->assertTrue($result['success']);
        $product->refresh();
        $this->assertEquals(4500.0, (float) $product->harga_retail);
        $this->assertEquals(5, $product->min_qty_grosir_1);
        $this->assertEquals(8, $product->stok);
    }

    public function test_admin_can_access_sync_ui_and_kasir_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($admin)->get(route('admin.sinkronisasi.index'))->assertOk()->assertSee('Sinkronisasi REST API Server');
        $this->actingAs($kasir)->get(route('admin.sinkronisasi.index'))->assertForbidden();

        Http::fake([
            'http://127.0.0.1:8080/api/sync/ping' => Http::response([
                'status' => 'ok',
                'message' => 'MaPOS Sync Server is online.',
                'server_time' => now()->toIso8601String(),
            ], 200),
        ]);

        Livewire::actingAs($admin)
            ->test(SyncIndex::class)
            ->call('testConnection')
            ->assertSet('serverOnline', true);
    }

    public function test_admin_can_create_and_download_sqlite_backup_snapshot(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $tempDb = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mapos-test-src-'.uniqid().'.sqlite';
        $pdo = new \PDO('sqlite:'.$tempDb);
        $pdo->exec('CREATE TABLE sample (id INTEGER PRIMARY KEY, name TEXT); INSERT INTO sample (name) VALUES ("ok");');
        $pdo = null;

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => $tempDb]);

        $component = Livewire::actingAs($admin)
            ->test(SyncIndex::class)
            ->call('createLocalBackup');

        $backupFiles = glob(storage_path('app/backups/mapos-backup-*.sqlite'));
        $this->assertNotEmpty($backupFiles);

        $latest = basename(end($backupFiles));
        $component->call('downloadSavedBackup', $latest)->assertFileDownloaded($latest);
        $component->call('deleteBackup', $latest);

        $this->assertFileDoesNotExist(storage_path('app/backups/'.$latest));
        @unlink($tempDb);
    }

    public function test_sync_batch_syncs_products_and_sales_before_sale_payments_and_returns(): void
    {
        $user = User::factory()->create(['email' => 'kasir.batch@mapos.test']);
        $golonganId = (string) Str::uuid();
        $productId = (string) Str::uuid();
        $customerId = (string) Str::uuid();
        $shiftId = (string) Str::uuid();
        $saleId = (string) Str::uuid();
        $saleItemId = (string) Str::uuid();
        $paymentId = (string) Str::uuid();
        $returnId = (string) Str::uuid();
        $returnItemId = (string) Str::uuid();

        $payload = [
            'golongans' => [[
                'id' => $golonganId,
                'kode_golongan' => '99',
                'nama_golongan' => 'Batch Kategori',
            ]],
            'products' => [[
                'id' => $productId,
                'kode_produk' => 'PRD-BATCH-1',
                'nama' => 'Produk Baru Desktop',
                'golongan_id' => $golonganId,
                'satuan' => 'pcs',
                'harga_beli' => 5000,
                'harga_retail' => 10000,
                'stok_etalase' => 18,
                'stok_gudang' => 0,
                'stok' => 18,
            ]],
            'customers' => [[
                'id' => $customerId,
                'kode_member' => 'MBR-B1',
                'nama' => 'Member Batch',
                'poin' => 0,
                'limit_kredit' => 500000,
                'piutang_berjalan' => 0,
            ]],
            'cash_shifts' => [[
                'id' => $shiftId,
                'user_id' => $user->id,
                'nama_petugas' => 'Kasir Batch',
                'waktu_buka' => now()->toIso8601String(),
                'saldo_awal' => 100000,
                'saldo_akhir_sistem' => 100000,
                'status' => 'open',
            ]],
            'sales' => [[
                'id' => $saleId,
                'no_transaksi' => 'TRX-BATCH-1',
                'kasir_id' => $user->id,
                'cash_shift_id' => $shiftId,
                'customer_id' => $customerId,
                'waktu' => now()->toIso8601String(),
                'total' => 20000,
                'bayar' => 10000,
                'kembali' => 0,
                'metode_pembayaran' => 'tempo',
                'status_pembayaran' => 'piutang',
                'sisa_piutang' => 10000,
                'items' => [[
                    'id' => $saleItemId,
                    'product_id' => $productId,
                    'kode_produk' => 'PRD-BATCH-1',
                    'nama_produk' => 'Produk Baru Desktop',
                    'qty' => 2,
                    'harga_satuan' => 10000,
                    'subtotal' => 20000,
                ]],
            ]],
            'sale_payments' => [[
                'id' => $paymentId,
                'sale_id' => $saleId,
                'customer_id' => $customerId,
                'user_id' => $user->id,
                'jumlah' => 10000,
                'metode' => 'cash',
                'waktu' => now()->toIso8601String(),
            ]],
            'sale_returns' => [[
                'id' => $returnId,
                'no_retur' => 'RTR-BATCH-1',
                'sale_id' => $saleId,
                'user_id' => $user->id,
                'cash_shift_id' => $shiftId,
                'metode_refund' => 'cash',
                'total_refund' => 10000,
                'waktu' => now()->toIso8601String(),
                'items' => [[
                    'id' => $returnItemId,
                    'sale_item_id' => $saleItemId,
                    'product_id' => $productId,
                    'nama_produk' => 'Produk Baru Desktop',
                    'qty' => 1,
                    'harga_satuan' => 10000,
                    'subtotal' => 10000,
                ]],
            ]],
        ];

        $this->withToken($this->apiToken)
            ->postJson('/api/sync/batch', $payload)
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'synced_sale_ids' => [$saleId],
            ]);

        $this->assertDatabaseHas('products', ['id' => $productId, 'stok' => 18]);
        $this->assertDatabaseHas('sales', ['id' => $saleId, 'cash_shift_id' => $shiftId, 'customer_id' => $customerId]);
        $this->assertDatabaseHas('sale_payments', ['id' => $paymentId, 'sale_id' => $saleId]);
        $this->assertDatabaseHas('sale_returns', ['id' => $returnId, 'sale_id' => $saleId]);
        $this->assertEquals(1, SaleReturnItem::where('sale_return_id', $returnId)->count());
    }

    public function test_pull_catalog_maps_employee_user_id_by_email(): void
    {
        $user = User::factory()->create(['email' => 'choerul@mapos.test']);
        $jabatanId = (string) Str::uuid();
        $empId = (string) Str::uuid();

        Http::fake([
            'http://127.0.0.1:8080/api/sync/catalog' => Http::response([
                'status' => 'success',
                'golongans' => [],
                'products' => [],
                'jabatans' => [[
                    'id' => $jabatanId,
                    'kode_jabatan' => 'JBT-MAP',
                    'nama_jabatan' => 'Kasir',
                    'gaji_pokok_default' => 2000000,
                    'tunjangan_default' => 0,
                ]],
                'employees' => [[
                    'id' => $empId,
                    'kode_karyawan' => 'EMP-MAP',
                    'jabatan_id' => $jabatanId,
                    'user_id' => 999,
                    'user' => ['id' => 999, 'email' => 'choerul@mapos.test'],
                    'nama' => 'Choerul',
                    'gaji_pokok' => 2000000,
                    'tunjangan' => 0,
                    'status_aktif' => true,
                ]],
            ], 200),
        ]);

        $res = (new SyncService)->pullCatalog();

        $this->assertTrue($res['success']);
        $this->assertDatabaseHas('employees', [
            'id' => $empId,
            'user_id' => $user->id,
        ]);
    }
}
