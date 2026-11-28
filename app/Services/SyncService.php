<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Dropshipper;
use App\Models\Employee;
use App\Models\Golongan;
use App\Models\Jabatan;
use App\Models\Payroll;
use App\Models\Pengeluaran;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\StoreProfile;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncService
{
    protected string $serverUrl;

    protected string $apiToken;

    protected int $timeout;

    public function __construct()
    {
        $this->serverUrl = rtrim(config('sync.server_url', 'http://127.0.0.1:8080'), '/');
        $this->apiToken = config('sync.api_token', '');
        $this->timeout = (int) config('sync.timeout', 15);
    }

    /**
     * Check if the central sync server is reachable and token is valid.
     *
     * @return array{success: bool, message: string, server_time: ?string}
     */
    public function ping(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->acceptJson()
                ->get("{$this->serverUrl}/api/sync/ping");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json('message', 'Server is online.'),
                    'server_time' => $response->json('server_time'),
                ];
            }

            return [
                'success' => false,
                'message' => 'Server returned HTTP '.$response->status().': '.$response->body(),
                'server_time' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Cannot connect to server: '.$e->getMessage(),
                'server_time' => null,
            ];
        }
    }

    /**
     * Push unsynced sales from local desktop database to central server.
     *
     * @return array{success: bool, count: int, message: string}
     */
    public function pushSales(int $limit = 50): array
    {
        $unsyncedSales = Sale::whereNull('synced_at')
            ->with('items')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($unsyncedSales->isEmpty()) {
            return [
                'success' => true,
                'count' => 0,
                'message' => 'No pending sales to sync.',
            ];
        }

        $payload = [
            'branch_id' => config('sync.branch_id'),
            'sales' => $unsyncedSales->map(fn (Sale $sale): array => $this->mapSaleToPayload($sale))->toArray(),
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->acceptJson()
                ->post("{$this->serverUrl}/api/sync/sales", $payload);

            if ($response->successful()) {
                $syncedIds = $response->json('synced_ids', []);

                Sale::whereIn('id', $syncedIds)->update(['synced_at' => now()]);

                return [
                    'success' => true,
                    'count' => count($syncedIds),
                    'message' => count($syncedIds).' sales successfully synced to cloud.',
                ];
            }

            return [
                'success' => false,
                'count' => 0,
                'message' => 'Sync failed: HTTP '.$response->status().' - '.$response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Sync push sales error: '.$e->getMessage());

            return [
                'success' => false,
                'count' => 0,
                'message' => 'Connection error while pushing sales: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Push master, shift, purchase, return, payroll, and expense records to central server.
     *
     * @return array{success: bool, counts: array<string, int>, message: string}
     */
    public function pushAllOperational(): array
    {
        $unsyncedSales = Sale::whereNull('synced_at')
            ->with('items')
            ->orderBy('created_at', 'asc')
            ->get();

        $payload = [
            'branch_id' => config('sync.branch_id'),
            'users' => User::query()->select(['name', 'email', 'role'])->get()->toArray(),
            'golongans' => Golongan::withTrashed()->get()->toArray(),
            'products' => Product::withTrashed()->get()->toArray(),
            'jabatans' => Jabatan::withTrashed()->get()->toArray(),
            'employees' => Employee::withTrashed()->with('user:id,email')->get()->toArray(),
            'customers' => Customer::withTrashed()->get()->toArray(),
            'dropshippers' => Dropshipper::withTrashed()->get()->toArray(),
            'suppliers' => Supplier::withTrashed()->get()->toArray(),
            'store_profile' => StoreProfile::first()?->toArray(),
            'cash_shifts' => CashShift::withTrashed()->get()->toArray(),
            'sales' => $unsyncedSales->map(fn (Sale $sale): array => $this->mapSaleToPayload($sale))->toArray(),
            'purchases' => Purchase::withTrashed()->with('items')->get()->toArray(),
            'sale_payments' => SalePayment::query()->get()->toArray(),
            'purchase_payments' => PurchasePayment::query()->get()->toArray(),
            'sale_returns' => SaleReturn::withTrashed()->with('items')->get()->toArray(),
            'purchase_returns' => PurchaseReturn::withTrashed()->with('items')->get()->toArray(),
            'payrolls' => Payroll::withTrashed()->get()->toArray(),
            'pengeluarans' => Pengeluaran::withTrashed()->get()->toArray(),
            'stock_movements' => StockMovement::query()->get()->toArray(),
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->acceptJson()
                ->post("{$this->serverUrl}/api/sync/batch", $payload);

            if ($response->successful()) {
                $counts = $response->json('counts', []);
                $syncedSaleIds = $response->json('synced_sale_ids', []);

                if (! empty($syncedSaleIds)) {
                    Sale::whereIn('id', $syncedSaleIds)->update(['synced_at' => now()]);
                }

                return [
                    'success' => true,
                    'counts' => $counts,
                    'sales_count' => count($syncedSaleIds),
                    'message' => 'Batch operational data synced successfully.',
                ];
            }

            return [
                'success' => false,
                'counts' => [],
                'sales_count' => 0,
                'message' => 'Batch sync failed: HTTP '.$response->status().' - '.$response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Sync batch error: '.$e->getMessage());

            return [
                'success' => false,
                'counts' => [],
                'sales_count' => 0,
                'message' => 'Connection error during batch sync: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Pull updated products, categories, and master tables from central server into local database.
     *
     * @return array{success: bool, count: int, message: string}
     */
    public function pullCatalog(?string $since = null): array
    {
        try {
            $query = $since ? ['since' => $since] : [];

            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->acceptJson()
                ->get("{$this->serverUrl}/api/sync/catalog", $query);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'Failed to pull catalog: HTTP '.$response->status(),
                ];
            }

            $this->upsertFromPayload(Golongan::class, $response->json('golongans', []), true, fn (array $g): array => [
                'kode_golongan' => $g['kode_golongan'],
                'nama_golongan' => $g['nama_golongan'],
                'deleted_at' => $g['deleted_at'] ?? null,
            ]);

            $pendingProductIds = Sale::whereNull('synced_at')
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->pluck('sale_items.product_id')
                ->unique()
                ->all();

            $products = $response->json('products', []);
            $count = 0;

            foreach ($products as $p) {
                $existing = Product::withTrashed()->find($p['id']);
                $hasPendingLocalSales = in_array($p['id'], $pendingProductIds, true);

                $attributes = [
                    'kode_produk' => $p['kode_produk'],
                    'nama' => $p['nama'],
                    'golongan_id' => $p['golongan_id'] ?? null,
                    'satuan' => $p['satuan'] ?? 'pcs',
                    'harga_beli' => $p['harga_beli'] ?? 0,
                    'harga_retail' => $p['harga_retail'] ?? 0,
                    'harga_grosir_1' => $p['harga_grosir_1'] ?? null,
                    'min_qty_grosir_1' => $p['min_qty_grosir_1'] ?? null,
                    'harga_grosir_2' => $p['harga_grosir_2'] ?? null,
                    'min_qty_grosir_2' => $p['min_qty_grosir_2'] ?? null,
                    'harga_grosir_3' => $p['harga_grosir_3'] ?? null,
                    'min_qty_grosir_3' => $p['min_qty_grosir_3'] ?? null,
                    'diskon' => $p['diskon'] ?? 0,
                    'promo_mulai' => $p['promo_mulai'] ?? null,
                    'promo_sampai' => $p['promo_sampai'] ?? null,
                    'min_stok' => $p['min_stok'] ?? 0,
                    'deleted_at' => $p['deleted_at'] ?? null,
                ];

                if (! $hasPendingLocalSales || ! $existing) {
                    $attributes['stok_etalase'] = $p['stok_etalase'] ?? 0;
                    $attributes['stok_gudang'] = $p['stok_gudang'] ?? 0;
                    $attributes['stok'] = $p['stok'] ?? 0;
                }

                $record = $existing ?? new Product;
                $record->id = $p['id'];
                $record->fill($attributes);
                $record->save();
                $count++;
            }

            $this->upsertFromPayload(Customer::class, $response->json('customers', []), true, fn (array $c): array => [
                'kode_member' => $c['kode_member'],
                'nama' => $c['nama'],
                'telepon' => $c['telepon'] ?? null,
                'alamat' => $c['alamat'] ?? null,
                'poin' => $c['poin'] ?? 0,
                'limit_kredit' => $c['limit_kredit'] ?? 0,
                'piutang_berjalan' => $c['piutang_berjalan'] ?? 0,
                'deleted_at' => $c['deleted_at'] ?? null,
            ]);

            $this->upsertFromPayload(Dropshipper::class, $response->json('dropshippers', []), true, fn (array $d): array => [
                'kode_dropshipper' => $d['kode_dropshipper'],
                'nama' => $d['nama'],
                'telepon' => $d['telepon'] ?? null,
                'alamat' => $d['alamat'] ?? null,
                'diskon_khusus' => $d['diskon_khusus'] ?? 0,
                'status_aktif' => (bool) ($d['status_aktif'] ?? true),
                'deleted_at' => $d['deleted_at'] ?? null,
            ]);

            $this->upsertFromPayload(Supplier::class, $response->json('suppliers', []), true, fn (array $s): array => [
                'nama' => $s['nama'],
                'telepon' => $s['telepon'] ?? null,
                'alamat' => $s['alamat'] ?? null,
                'nama_kontak' => $s['nama_kontak'] ?? null,
                'catatan' => $s['catatan'] ?? null,
                'deleted_at' => $s['deleted_at'] ?? null,
            ]);

            $this->upsertFromPayload(Jabatan::class, $response->json('jabatans', []), true, fn (array $j): array => [
                'kode_jabatan' => $j['kode_jabatan'],
                'nama_jabatan' => $j['nama_jabatan'],
                'gaji_pokok_default' => $j['gaji_pokok_default'] ?? 0,
                'tunjangan_default' => $j['tunjangan_default'] ?? 0,
                'deskripsi' => $j['deskripsi'] ?? null,
                'deleted_at' => $j['deleted_at'] ?? null,
            ]);

            $userIdsByEmail = [];
            foreach (User::query()->select(['id', 'email'])->get() as $u) {
                $userIdsByEmail[strtolower($u->email)] = $u->id;
            }

            $this->upsertFromPayload(Employee::class, $response->json('employees', []), true, function (array $e) use ($userIdsByEmail): array {
                $email = $e['user']['email'] ?? $e['user_email'] ?? null;

                return [
                    'kode_karyawan' => $e['kode_karyawan'],
                    'jabatan_id' => $e['jabatan_id'],
                    'user_id' => ! empty($email) ? ($userIdsByEmail[strtolower($email)] ?? null) : null,
                    'nama' => $e['nama'],
                    'telepon' => $e['telepon'] ?? null,
                    'alamat' => $e['alamat'] ?? null,
                    'tanggal_masuk' => $e['tanggal_masuk'] ?? null,
                    'gaji_pokok' => $e['gaji_pokok'] ?? 0,
                    'tunjangan' => $e['tunjangan'] ?? 0,
                    'status_aktif' => (bool) ($e['status_aktif'] ?? true),
                    'deleted_at' => $e['deleted_at'] ?? null,
                ];
            });

            $storeProfile = $response->json('store_profile');
            if (is_array($storeProfile)) {
                $localProfile = StoreProfile::getProfile();
                $localProfile->update([
                    'nama_toko' => $storeProfile['nama_toko'] ?? $localProfile->nama_toko,
                    'slogan' => $storeProfile['slogan'] ?? $localProfile->slogan,
                    'alamat' => $storeProfile['alamat'] ?? $localProfile->alamat,
                    'telepon' => $storeProfile['telepon'] ?? $localProfile->telepon,
                    'email' => $storeProfile['email'] ?? $localProfile->email,
                    'website' => $storeProfile['website'] ?? $localProfile->website,
                    'footer_struk' => $storeProfile['footer_struk'] ?? $localProfile->footer_struk,
                    'ukuran_kertas' => $storeProfile['ukuran_kertas'] ?? $localProfile->ukuran_kertas,
                ]);
            }

            return [
                'success' => true,
                'count' => $count,
                'message' => "Successfully updated {$count} products from server.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Connection error while pulling catalog: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Execute full two-way sync: push operational dependencies, push sales, then pull catalog.
     *
     * @return array{success: bool, batch: array<string, mixed>, sales: array<string, mixed>, catalog: array<string, mixed>, message: string}
     */
    public function syncAll(): array
    {
        $batch = $this->pushAllOperational();
        $totalSales = (int) ($batch['sales_count'] ?? 0);
        $sales = ['success' => true, 'count' => $totalSales, 'message' => ''];

        if ($batch['success']) {
            do {
                $res = $this->pushSales(200);
                if (! $res['success']) {
                    $sales = $res;
                    break;
                }
                $totalSales += $res['count'];
                $sales['count'] = $totalSales;
            } while ($res['count'] === 200);
        }

        $catalog = $this->pullCatalog();

        $success = $batch['success'] && $sales['success'] && $catalog['success'];

        return [
            'success' => $success,
            'batch' => $batch,
            'sales' => $sales,
            'catalog' => $catalog,
            'message' => $success
                ? "Sinkronisasi selesai: {$totalSales} transaksi dikirim, {$catalog['count']} produk diperbarui."
                : trim($batch['message'].' '.$sales['message'].' '.$catalog['message']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapSaleToPayload(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'no_transaksi' => $sale->no_transaksi,
            'kasir_id' => $sale->kasir_id,
            'cash_shift_id' => $sale->cash_shift_id,
            'customer_id' => $sale->customer_id,
            'waktu' => $sale->waktu?->toIso8601String() ?? now()->toIso8601String(),
            'total' => (float) $sale->total,
            'bayar' => (float) $sale->bayar,
            'kembali' => (float) $sale->kembali,
            'metode_pembayaran' => $sale->metode_pembayaran ?? 'cash',
            'status_pembayaran' => $sale->status_pembayaran ?? 'lunas',
            'sisa_piutang' => (float) ($sale->sisa_piutang ?? 0),
            'poin_didapat' => (int) ($sale->poin_didapat ?? 0),
            'poin_dipakai' => (int) ($sale->poin_dipakai ?? 0),
            'potongan_poin' => (float) ($sale->potongan_poin ?? 0),
            'is_dropship' => (bool) ($sale->is_dropship ?? false),
            'dropshipper_id' => $sale->dropshipper_id,
            'dropship_penerima_nama' => $sale->dropship_penerima_nama,
            'dropship_penerima_telepon' => $sale->dropship_penerima_telepon,
            'dropship_penerima_alamat' => $sale->dropship_penerima_alamat,
            'items' => $sale->items->map(function ($item): array {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'kode_produk' => $item->kode_produk,
                    'nama_produk' => $item->nama_produk,
                    'qty' => (int) $item->qty,
                    'harga_satuan' => (float) $item->harga_satuan,
                    'subtotal' => (float) $item->subtotal,
                ];
            })->toArray(),
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): array<string, mixed>  $mapper
     */
    protected function upsertFromPayload(string $modelClass, array $rows, bool $softDeletes, callable $mapper): void
    {
        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            /** @var Model $record */
            $record = ($softDeletes ? $modelClass::withTrashed()->find($row['id']) : $modelClass::find($row['id'])) ?? new $modelClass;
            $record->setAttribute('id', $row['id']);
            $record->fill($mapper($row));
            $record->save();
        }
    }
}
