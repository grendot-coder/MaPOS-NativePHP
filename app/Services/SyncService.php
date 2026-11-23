<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
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
            'sales' => $unsyncedSales->map(function (Sale $sale) {
                return [
                    'id' => $sale->id,
                    'no_transaksi' => $sale->no_transaksi,
                    'kasir_id' => $sale->kasir_id,
                    'cash_shift_id' => $sale->cash_shift_id,
                    'waktu' => $sale->waktu?->toIso8601String() ?? now()->toIso8601String(),
                    'total' => (float) $sale->total,
                    'bayar' => (float) $sale->bayar,
                    'kembali' => (float) $sale->kembali,
                    'items' => $sale->items->map(function ($item) {
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
            })->toArray(),
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
     * Pull updated products and catalog from central server into local database.
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

            $products = $response->json('products', []);
            $count = 0;

            foreach ($products as $p) {
                Product::withTrashed()->updateOrCreate(
                    ['id' => $p['id']],
                    [
                        'kode_produk' => $p['kode_produk'],
                        'nama' => $p['nama'],
                        'golongan_id' => $p['golongan_id'] ?? null,
                        'satuan' => $p['satuan'] ?? 'pcs',
                        'harga_beli' => $p['harga_beli'] ?? 0,
                        'harga_retail' => $p['harga_retail'] ?? 0,
                        'harga_grosir_1' => $p['harga_grosir_1'] ?? null,
                        'harga_grosir_2' => $p['harga_grosir_2'] ?? null,
                        'harga_grosir_3' => $p['harga_grosir_3'] ?? null,
                        'diskon' => $p['diskon'] ?? 0,
                        'stok_etalase' => $p['stok_etalase'] ?? 0,
                        'stok_gudang' => $p['stok_gudang'] ?? 0,
                        'stok' => $p['stok'] ?? 0,
                        'min_stok' => $p['min_stok'] ?? 0,
                        'deleted_at' => $p['deleted_at'] ?? null,
                    ]
                );
                $count++;
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
}
