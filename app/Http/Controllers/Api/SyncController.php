<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Handshake endpoint to verify server connectivity and API token.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'MaPOS Sync Server is online.',
            'server_time' => now()->toIso8601String(),
            'app_name' => config('app.name'),
        ]);
    }

    /**
     * Receive sales batch from desktop client.
     */
    public function syncSales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales' => ['required', 'array'],
            'sales.*.id' => ['required', 'string'],
            'sales.*.no_transaksi' => ['required', 'string'],
            'sales.*.kasir_id' => ['nullable', 'integer'],
            'sales.*.cash_shift_id' => ['nullable', 'integer'],
            'sales.*.waktu' => ['required'],
            'sales.*.total' => ['required', 'numeric'],
            'sales.*.bayar' => ['required', 'numeric'],
            'sales.*.kembali' => ['required', 'numeric'],
            'sales.*.items' => ['required', 'array'],
            'sales.*.items.*.id' => ['required', 'string'],
            'sales.*.items.*.product_id' => ['required', 'string'],
            'sales.*.items.*.kode_produk' => ['required', 'string'],
            'sales.*.items.*.nama_produk' => ['required', 'string'],
            'sales.*.items.*.qty' => ['required', 'integer'],
            'sales.*.items.*.harga_satuan' => ['required', 'numeric'],
            'sales.*.items.*.subtotal' => ['required', 'numeric'],
        ]);

        $syncedIds = [];

        DB::transaction(function () use ($validated, &$syncedIds): void {
            foreach ($validated['sales'] as $saleData) {
                $sale = Sale::withTrashed()->find($saleData['id']) ?? new Sale;
                $sale->id = $saleData['id'];
                $sale->fill([
                    'no_transaksi' => $saleData['no_transaksi'],
                    'kasir_id' => $saleData['kasir_id'] ?? 1,
                    'cash_shift_id' => $saleData['cash_shift_id'] ?? null,
                    'waktu' => $saleData['waktu'],
                    'total' => $saleData['total'],
                    'bayar' => $saleData['bayar'],
                    'kembali' => $saleData['kembali'],
                    'synced_at' => now(),
                ]);
                $sale->save();

                foreach ($saleData['items'] as $itemData) {
                    $item = SaleItem::find($itemData['id']) ?? new SaleItem;
                    $item->id = $itemData['id'];
                    $item->fill([
                        'sale_id' => $sale->id,
                        'product_id' => $itemData['product_id'],
                        'kode_produk' => $itemData['kode_produk'],
                        'nama_produk' => $itemData['nama_produk'],
                        'qty' => $itemData['qty'],
                        'harga_satuan' => $itemData['harga_satuan'],
                        'subtotal' => $itemData['subtotal'],
                    ]);
                    $item->save();
                }

                $syncedIds[] = $sale->id;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => count($syncedIds).' sales synced successfully.',
            'synced_ids' => $syncedIds,
        ]);
    }

    /**
     * Download products/catalog updated since given timestamp.
     */
    public function getCatalog(Request $request): JsonResponse
    {
        $since = $request->query('since');

        $query = Product::query()->with('golongan');

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $products = $query->get();

        return response()->json([
            'status' => 'success',
            'server_time' => now()->toIso8601String(),
            'count' => $products->count(),
            'products' => $products,
        ]);
    }
}
