<?php

namespace App\Http\Controllers\Api;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
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
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Models\StoreProfile;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'app_version' => config('nativephp.version', '1.1.2'),
        ]);
    }

    /**
     * Receive sales batch from desktop client with idempotent stock deduction.
     */
    public function syncSales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales' => ['required', 'array'],
            'sales.*.id' => ['required', 'string'],
            'sales.*.no_transaksi' => ['required', 'string'],
            'sales.*.kasir_id' => ['nullable', 'integer'],
            'sales.*.cash_shift_id' => ['nullable', 'string'],
            'sales.*.customer_id' => ['nullable', 'string'],
            'sales.*.waktu' => ['required'],
            'sales.*.total' => ['required', 'numeric'],
            'sales.*.bayar' => ['required', 'numeric'],
            'sales.*.kembali' => ['required', 'numeric'],
            'sales.*.metode_pembayaran' => ['nullable', 'string', 'max:20'],
            'sales.*.status_pembayaran' => ['nullable', 'string', 'max:20'],
            'sales.*.sisa_piutang' => ['nullable', 'numeric'],
            'sales.*.poin_didapat' => ['nullable', 'integer'],
            'sales.*.poin_dipakai' => ['nullable', 'integer'],
            'sales.*.potongan_poin' => ['nullable', 'numeric'],
            'sales.*.is_dropship' => ['nullable', 'boolean'],
            'sales.*.dropshipper_id' => ['nullable', 'string'],
            'sales.*.dropship_penerima_nama' => ['nullable', 'string', 'max:150'],
            'sales.*.dropship_penerima_telepon' => ['nullable', 'string', 'max:50'],
            'sales.*.dropship_penerima_alamat' => ['nullable', 'string'],
            'sales.*.items' => ['required', 'array'],
            'sales.*.items.*.id' => ['required', 'string'],
            'sales.*.items.*.product_id' => ['required', 'string'],
            'sales.*.items.*.kode_produk' => ['required', 'string'],
            'sales.*.items.*.nama_produk' => ['required', 'string'],
            'sales.*.items.*.qty' => ['required', 'integer'],
            'sales.*.items.*.harga_satuan' => ['required', 'numeric'],
            'sales.*.items.*.subtotal' => ['required', 'numeric'],
        ]);

        $fallbackUserId = $this->resolveFallbackUserId();
        $syncedIds = [];

        DB::transaction(function () use ($validated, &$syncedIds, $fallbackUserId): void {
            $syncedIds = $this->upsertSales($validated['sales'], $fallbackUserId);
        });

        return response()->json([
            'status' => 'success',
            'message' => count($syncedIds).' sales synced successfully.',
            'synced_ids' => $syncedIds,
        ]);
    }

    /**
     * Receive full operational and master records from desktop client in FK order.
     */
    public function syncBatch(Request $request): JsonResponse
    {
        $payload = $request->all();
        $fallbackUserId = $this->resolveFallbackUserId();
        $counts = [];
        $syncedSaleIds = [];

        DB::transaction(function () use ($payload, $fallbackUserId, &$counts, &$syncedSaleIds): void {
            if (! empty($payload['users']) && is_array($payload['users'])) {
                $counts['users'] = $this->syncUsers($payload['users']);
            }

            if (! empty($payload['golongans']) && is_array($payload['golongans'])) {
                $counts['golongans'] = $this->upsertUuidRows(Golongan::class, $payload['golongans'], true, fn (array $row): array => [
                    'kode_golongan' => $row['kode_golongan'],
                    'nama_golongan' => $row['nama_golongan'],
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['products']) && is_array($payload['products'])) {
                $counts['products'] = $this->upsertUuidRows(Product::class, $payload['products'], true, fn (array $row): array => [
                    'kode_produk' => $row['kode_produk'],
                    'nama' => $row['nama'],
                    'golongan_id' => $row['golongan_id'],
                    'satuan' => $row['satuan'] ?? 'pcs',
                    'harga_beli' => $row['harga_beli'] ?? 0,
                    'harga_retail' => $row['harga_retail'] ?? 0,
                    'harga_grosir_1' => $row['harga_grosir_1'] ?? null,
                    'min_qty_grosir_1' => $row['min_qty_grosir_1'] ?? null,
                    'harga_grosir_2' => $row['harga_grosir_2'] ?? null,
                    'min_qty_grosir_2' => $row['min_qty_grosir_2'] ?? null,
                    'harga_grosir_3' => $row['harga_grosir_3'] ?? null,
                    'min_qty_grosir_3' => $row['min_qty_grosir_3'] ?? null,
                    'diskon' => $row['diskon'] ?? 0,
                    'promo_mulai' => $row['promo_mulai'] ?? null,
                    'promo_sampai' => $row['promo_sampai'] ?? null,
                    'stok_etalase' => $row['stok_etalase'] ?? 0,
                    'stok_gudang' => $row['stok_gudang'] ?? 0,
                    'stok' => $row['stok'] ?? 0,
                    'min_stok' => $row['min_stok'] ?? 0,
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['jabatans']) && is_array($payload['jabatans'])) {
                $counts['jabatans'] = $this->upsertUuidRows(Jabatan::class, $payload['jabatans'], true, fn (array $row): array => [
                    'kode_jabatan' => $row['kode_jabatan'],
                    'nama_jabatan' => $row['nama_jabatan'],
                    'gaji_pokok_default' => $row['gaji_pokok_default'] ?? 0,
                    'tunjangan_default' => $row['tunjangan_default'] ?? 0,
                    'deskripsi' => $row['deskripsi'] ?? null,
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['employees']) && is_array($payload['employees'])) {
                $userIdsByEmail = User::pluck('id', 'email')->all();

                $counts['employees'] = $this->upsertUuidRows(Employee::class, $payload['employees'], true, function (array $row) use ($userIdsByEmail, $fallbackUserId): array {
                    $email = $row['user']['email'] ?? $row['user_email'] ?? null;
                    $resolvedUserId = null;
                    if (! empty($email)) {
                        $resolvedUserId = $userIdsByEmail[strtolower($email)] ?? null;
                    } elseif (! empty($row['user_id'])) {
                        $resolvedUserId = $this->resolveUserId((int) $row['user_id'], $fallbackUserId);
                    }

                    return [
                        'kode_karyawan' => $row['kode_karyawan'],
                        'jabatan_id' => $row['jabatan_id'],
                        'user_id' => $resolvedUserId,
                        'nama' => $row['nama'],
                        'telepon' => $row['telepon'] ?? null,
                        'alamat' => $row['alamat'] ?? null,
                        'tanggal_masuk' => $row['tanggal_masuk'] ?? null,
                        'gaji_pokok' => $row['gaji_pokok'] ?? 0,
                        'tunjangan' => $row['tunjangan'] ?? 0,
                        'status_aktif' => (bool) ($row['status_aktif'] ?? true),
                        'deleted_at' => $row['deleted_at'] ?? null,
                    ];
                });
            }

            if (! empty($payload['customers']) && is_array($payload['customers'])) {
                $counts['customers'] = $this->upsertUuidRows(Customer::class, $payload['customers'], true, fn (array $row): array => [
                    'kode_member' => $row['kode_member'],
                    'nama' => $row['nama'],
                    'telepon' => $row['telepon'] ?? null,
                    'alamat' => $row['alamat'] ?? null,
                    'poin' => $row['poin'] ?? 0,
                    'limit_kredit' => $row['limit_kredit'] ?? 0,
                    'piutang_berjalan' => $row['piutang_berjalan'] ?? 0,
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['dropshippers']) && is_array($payload['dropshippers'])) {
                $counts['dropshippers'] = $this->upsertUuidRows(Dropshipper::class, $payload['dropshippers'], true, fn (array $row): array => [
                    'kode_dropshipper' => $row['kode_dropshipper'],
                    'nama' => $row['nama'],
                    'telepon' => $row['telepon'] ?? null,
                    'alamat' => $row['alamat'] ?? null,
                    'diskon_khusus' => $row['diskon_khusus'] ?? 0,
                    'status_aktif' => (bool) ($row['status_aktif'] ?? true),
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['suppliers']) && is_array($payload['suppliers'])) {
                $counts['suppliers'] = $this->upsertUuidRows(Supplier::class, $payload['suppliers'], true, fn (array $row): array => [
                    'nama' => $row['nama'],
                    'telepon' => $row['telepon'] ?? null,
                    'alamat' => $row['alamat'] ?? null,
                    'nama_kontak' => $row['nama_kontak'] ?? null,
                    'catatan' => $row['catatan'] ?? null,
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['store_profile']) && is_array($payload['store_profile'])) {
                $profile = StoreProfile::getProfile();
                $profile->update([
                    'nama_toko' => $payload['store_profile']['nama_toko'] ?? $profile->nama_toko,
                    'slogan' => $payload['store_profile']['slogan'] ?? $profile->slogan,
                    'alamat' => $payload['store_profile']['alamat'] ?? $profile->alamat,
                    'telepon' => $payload['store_profile']['telepon'] ?? $profile->telepon,
                    'email' => $payload['store_profile']['email'] ?? $profile->email,
                    'website' => $payload['store_profile']['website'] ?? $profile->website,
                    'footer_struk' => $payload['store_profile']['footer_struk'] ?? $profile->footer_struk,
                    'ukuran_kertas' => $payload['store_profile']['ukuran_kertas'] ?? $profile->ukuran_kertas,
                ]);
                $counts['store_profile'] = 1;
            }

            if (! empty($payload['cash_shifts']) && is_array($payload['cash_shifts'])) {
                $counts['cash_shifts'] = $this->upsertUuidRows(CashShift::class, $payload['cash_shifts'], true, fn (array $row): array => [
                    'user_id' => $this->resolveUserId($row['user_id'] ?? null, $fallbackUserId),
                    'employee_id' => $row['employee_id'] ?? null,
                    'nama_petugas' => $row['nama_petugas'] ?? null,
                    'waktu_buka' => $row['waktu_buka'],
                    'waktu_tutup' => $row['waktu_tutup'] ?? null,
                    'saldo_awal' => $row['saldo_awal'] ?? 0,
                    'saldo_akhir_sistem' => $row['saldo_akhir_sistem'] ?? 0,
                    'saldo_akhir_fisik' => $row['saldo_akhir_fisik'] ?? null,
                    'selisih' => $row['selisih'] ?? 0,
                    'status' => $row['status'] ?? 'open',
                    'catatan' => $row['catatan'] ?? null,
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['sales']) && is_array($payload['sales'])) {
                $syncedSaleIds = $this->upsertSales($payload['sales'], $fallbackUserId, deductStock: empty($payload['products']));
                $counts['sales'] = count($syncedSaleIds);
            }

            if (! empty($payload['purchases']) && is_array($payload['purchases'])) {
                $counts['purchases'] = $this->syncPurchases($payload['purchases'], $fallbackUserId);
            }

            if (! empty($payload['sale_payments']) && is_array($payload['sale_payments'])) {
                $counts['sale_payments'] = $this->upsertUuidRows(SalePayment::class, $payload['sale_payments'], false, fn (array $row): array => [
                    'sale_id' => $row['sale_id'],
                    'customer_id' => $row['customer_id'],
                    'user_id' => $this->resolveUserId($row['user_id'] ?? null, $fallbackUserId),
                    'jumlah' => $row['jumlah'],
                    'metode' => $row['metode'] ?? 'cash',
                    'catatan' => $row['catatan'] ?? null,
                    'waktu' => $row['waktu'],
                ]);
            }

            if (! empty($payload['purchase_payments']) && is_array($payload['purchase_payments'])) {
                $counts['purchase_payments'] = $this->upsertUuidRows(PurchasePayment::class, $payload['purchase_payments'], false, fn (array $row): array => [
                    'purchase_id' => $row['purchase_id'],
                    'user_id' => $this->resolveUserId($row['user_id'] ?? null, $fallbackUserId),
                    'jumlah' => $row['jumlah'],
                    'metode' => $row['metode'] ?? 'cash',
                    'catatan' => $row['catatan'] ?? null,
                    'waktu' => $row['waktu'],
                ]);
            }

            if (! empty($payload['sale_returns']) && is_array($payload['sale_returns'])) {
                $counts['sale_returns'] = $this->syncSaleReturns($payload['sale_returns'], $fallbackUserId);
            }

            if (! empty($payload['purchase_returns']) && is_array($payload['purchase_returns'])) {
                $counts['purchase_returns'] = $this->syncPurchaseReturns($payload['purchase_returns'], $fallbackUserId);
            }

            if (! empty($payload['payrolls']) && is_array($payload['payrolls'])) {
                $counts['payrolls'] = $this->upsertUuidRows(Payroll::class, $payload['payrolls'], true, fn (array $row): array => [
                    'no_slip' => $row['no_slip'],
                    'employee_id' => $row['employee_id'],
                    'user_id' => $this->resolveUserId($row['user_id'] ?? null, $fallbackUserId),
                    'periode_bulan' => $row['periode_bulan'],
                    'gaji_pokok' => $row['gaji_pokok'] ?? 0,
                    'tunjangan' => $row['tunjangan'] ?? 0,
                    'bonus' => $row['bonus'] ?? 0,
                    'potongan_kasbon' => $row['potongan_kasbon'] ?? 0,
                    'potongan_lain' => $row['potongan_lain'] ?? 0,
                    'total_diterima' => $row['total_diterima'] ?? 0,
                    'status' => $row['status'] ?? 'draft',
                    'tanggal_bayar' => $row['tanggal_bayar'] ?? null,
                    'catatan' => $row['catatan'] ?? null,
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['pengeluarans']) && is_array($payload['pengeluarans'])) {
                $counts['pengeluarans'] = $this->upsertUuidRows(Pengeluaran::class, $payload['pengeluarans'], true, fn (array $row): array => [
                    'no_referensi' => $row['no_referensi'],
                    'kategori' => $row['kategori'],
                    'nama_karyawan' => $row['nama_karyawan'] ?? null,
                    'employee_id' => $row['employee_id'] ?? null,
                    'payroll_id' => $row['payroll_id'] ?? null,
                    'nominal' => $row['nominal'] ?? 0,
                    'keterangan' => $row['keterangan'],
                    'user_id' => $this->resolveUserId($row['user_id'] ?? null, $fallbackUserId),
                    'cash_shift_id' => $row['cash_shift_id'] ?? null,
                    'waktu' => $row['waktu'],
                    'deleted_at' => $row['deleted_at'] ?? null,
                ]);
            }

            if (! empty($payload['stock_movements']) && is_array($payload['stock_movements'])) {
                $counts['stock_movements'] = $this->upsertUuidRows(StockMovement::class, $payload['stock_movements'], false, fn (array $row): array => [
                    'product_id' => $row['product_id'],
                    'jenis' => $row['jenis'],
                    'jumlah' => $row['jumlah'],
                    'saldo_sesudah' => $row['saldo_sesudah'],
                    'keterangan' => $row['keterangan'] ?? null,
                    'referensi_id' => $row['referensi_id'] ?? null,
                    'referensi_type' => $row['referensi_type'] ?? null,
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Batch data synced successfully.',
            'counts' => $counts,
            'synced_sale_ids' => $syncedSaleIds,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Download products, categories, and master data updated since given timestamp.
     */
    public function getCatalog(Request $request): JsonResponse
    {
        $since = $request->query('since');

        $productQuery = Product::withTrashed()->with('golongan');
        $golonganQuery = Golongan::withTrashed();
        $customerQuery = Customer::withTrashed();
        $dropshipperQuery = Dropshipper::withTrashed();
        $supplierQuery = Supplier::withTrashed();
        $jabatanQuery = Jabatan::withTrashed();
        $employeeQuery = Employee::withTrashed()->with('user:id,email');

        if ($since) {
            foreach ([$productQuery, $golonganQuery, $customerQuery, $dropshipperQuery, $supplierQuery, $jabatanQuery, $employeeQuery] as $q) {
                $q->where('updated_at', '>=', $since);
            }
        }

        $products = $productQuery->get();

        return response()->json([
            'status' => 'success',
            'server_time' => now()->toIso8601String(),
            'count' => $products->count(),
            'golongans' => $golonganQuery->get(),
            'products' => $products,
            'customers' => $customerQuery->get(),
            'dropshippers' => $dropshipperQuery->get(),
            'suppliers' => $supplierQuery->get(),
            'jabatans' => $jabatanQuery->get(),
            'employees' => $employeeQuery->get(),
            'store_profile' => StoreProfile::first(),
        ]);
    }

    protected function resolveFallbackUserId(): int
    {
        return (int) (User::query()->orderBy('id')->value('id') ?? 1);
    }

    /**
     * Upsert sales + sale_items in batch/endpoint and optionally deduct server stock for brand-new sales.
     *
     * @param  array<int, array<string, mixed>>  $salesData
     * @return list<string>
     */
    protected function upsertSales(array $salesData, int $fallbackUserId, bool $deductStock = true): array
    {
        $syncedIds = [];

        foreach ($salesData as $saleData) {
            if (empty($saleData['id'])) {
                continue;
            }

            $existingSale = Sale::withTrashed()->find($saleData['id']);
            $isNewSale = $existingSale === null;

            $sale = $existingSale ?? new Sale;
            $sale->id = $saleData['id'];

            $cashShiftId = ! empty($saleData['cash_shift_id']) && CashShift::withTrashed()->whereKey($saleData['cash_shift_id'])->exists()
                ? $saleData['cash_shift_id']
                : null;
            $customerId = ! empty($saleData['customer_id']) && Customer::withTrashed()->whereKey($saleData['customer_id'])->exists()
                ? $saleData['customer_id']
                : null;
            $dropshipperId = ! empty($saleData['dropshipper_id']) && Dropshipper::withTrashed()->whereKey($saleData['dropshipper_id'])->exists()
                ? $saleData['dropshipper_id']
                : null;

            $sale->fill([
                'no_transaksi' => $saleData['no_transaksi'],
                'kasir_id' => $this->resolveUserId($saleData['kasir_id'] ?? null, $fallbackUserId),
                'cash_shift_id' => $cashShiftId,
                'customer_id' => $customerId,
                'waktu' => $saleData['waktu'],
                'total' => $saleData['total'],
                'bayar' => $saleData['bayar'],
                'kembali' => $saleData['kembali'],
                'metode_pembayaran' => $saleData['metode_pembayaran'] ?? 'cash',
                'status_pembayaran' => $saleData['status_pembayaran'] ?? 'lunas',
                'sisa_piutang' => $saleData['sisa_piutang'] ?? 0,
                'poin_didapat' => $saleData['poin_didapat'] ?? 0,
                'poin_dipakai' => $saleData['poin_dipakai'] ?? 0,
                'potongan_poin' => $saleData['potongan_poin'] ?? 0,
                'is_dropship' => (bool) ($saleData['is_dropship'] ?? false),
                'dropshipper_id' => $dropshipperId,
                'dropship_penerima_nama' => $saleData['dropship_penerima_nama'] ?? null,
                'dropship_penerima_telepon' => $saleData['dropship_penerima_telepon'] ?? null,
                'dropship_penerima_alamat' => $saleData['dropship_penerima_alamat'] ?? null,
                'synced_at' => now(),
            ]);
            $sale->save();

            foreach ($saleData['items'] ?? [] as $itemData) {
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

                if ($deductStock && $isNewSale) {
                    $this->deductProductStockForSync($itemData['product_id'], (int) $itemData['qty'], $sale);
                }
            }

            $syncedIds[] = $sale->id;
        }

        return $syncedIds;
    }

    protected function resolveUserId(?int $userId, int $fallbackUserId): int
    {
        if ($userId && User::whereKey($userId)->exists()) {
            return $userId;
        }

        return $fallbackUserId;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncUsers(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (empty($row['email'])) {
                continue;
            }

            $user = User::where('email', $row['email'])->first();
            if ($user) {
                $user->update([
                    'name' => $row['name'] ?? $user->name,
                    'role' => $row['role'] ?? $user->role,
                ]);
            } else {
                User::create([
                    'name' => $row['name'] ?? 'Staff MaPOS',
                    'email' => $row['email'],
                    'role' => $row['role'] ?? 'kasir',
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                ]);
            }
            $count++;
        }

        return $count;
    }

    protected function deductProductStockForSync(string $productId, int $qty, Sale $sale): void
    {
        /** @var Product|null $product */
        $product = Product::lockForUpdate()->find($productId);
        if (! $product || $qty <= 0) {
            return;
        }

        $etalase = (int) $product->stok_etalase;
        $gudang = (int) $product->stok_gudang;

        if ($etalase >= $qty) {
            $product->stok_etalase = $etalase - $qty;
        } elseif ($etalase + $gudang >= $qty) {
            $sisa = $qty - $etalase;
            $product->stok_etalase = 0;
            $product->stok_gudang = max(0, $gudang - $sisa);
        }

        $product->stok = max(0, (int) $product->stok - $qty);
        $product->save();

        StockMovement::create([
            'product_id' => $product->id,
            'jenis' => StockMovementType::Penjualan,
            'jumlah' => -$qty,
            'saldo_sesudah' => $product->stok,
            'keterangan' => "Sync POS {$sale->no_transaksi}",
            'referensi_id' => $sale->id,
            'referensi_type' => Sale::class,
        ]);
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): array<string, mixed>  $mapper
     */
    protected function upsertUuidRows(string $modelClass, array $rows, bool $softDeletes, callable $mapper): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            /** @var Model $record */
            $record = ($softDeletes ? $modelClass::withTrashed()->find($row['id']) : $modelClass::find($row['id'])) ?? new $modelClass;
            $record->setAttribute('id', $row['id']);
            $record->fill($mapper($row));
            $record->save();
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncPurchases(array $rows, int $fallbackUserId): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            $purchase = Purchase::withTrashed()->find($row['id']) ?? new Purchase;
            $purchase->id = $row['id'];
            $purchase->fill([
                'no_faktur' => $row['no_faktur'],
                'supplier_id' => $row['supplier_id'] ?? null,
                'nama_supplier' => $row['nama_supplier'],
                'user_id' => $this->resolveUserId($row['user_id'] ?? null, $fallbackUserId),
                'tanggal' => $row['tanggal'],
                'total' => $row['total'] ?? 0,
                'status_pembayaran' => $row['status_pembayaran'] ?? 'lunas',
                'jatuh_tempo' => $row['jatuh_tempo'] ?? null,
                'dibayar' => $row['dibayar'] ?? 0,
                'catatan' => $row['catatan'] ?? null,
                'deleted_at' => $row['deleted_at'] ?? null,
            ]);
            $purchase->save();

            foreach ($row['items'] ?? [] as $itemData) {
                $item = PurchaseItem::find($itemData['id']) ?? new PurchaseItem;
                $item->id = $itemData['id'];
                $item->fill([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'nama_produk' => $itemData['nama_produk'],
                    'qty' => $itemData['qty'],
                    'lokasi_tujuan' => $itemData['lokasi_tujuan'] ?? 'gudang',
                    'harga_beli' => $itemData['harga_beli'],
                    'subtotal' => $itemData['subtotal'],
                    'update_harga_beli_master' => (bool) ($itemData['update_harga_beli_master'] ?? false),
                ]);
                $item->save();
            }

            $count++;
        }

        return $count;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncSaleReturns(array $rows, int $fallbackUserId): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            $return = SaleReturn::withTrashed()->find($row['id']) ?? new SaleReturn;
            $return->id = $row['id'];
            $return->fill([
                'no_retur' => $row['no_retur'],
                'sale_id' => $row['sale_id'],
                'user_id' => $this->resolveUserId($row['user_id'] ?? null, $fallbackUserId),
                'cash_shift_id' => $row['cash_shift_id'] ?? null,
                'metode_refund' => $row['metode_refund'] ?? 'cash',
                'total_refund' => $row['total_refund'] ?? 0,
                'alasan' => $row['alasan'] ?? null,
                'waktu' => $row['waktu'],
                'deleted_at' => $row['deleted_at'] ?? null,
            ]);
            $return->save();

            foreach ($row['items'] ?? [] as $itemData) {
                $item = SaleReturnItem::find($itemData['id']) ?? new SaleReturnItem;
                $item->id = $itemData['id'];
                $item->fill([
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $itemData['sale_item_id'],
                    'product_id' => $itemData['product_id'],
                    'nama_produk' => $itemData['nama_produk'],
                    'qty' => $itemData['qty'],
                    'harga_satuan' => $itemData['harga_satuan'],
                    'subtotal' => $itemData['subtotal'],
                ]);
                $item->save();
            }

            $count++;
        }

        return $count;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncPurchaseReturns(array $rows, int $fallbackUserId): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            $return = PurchaseReturn::withTrashed()->find($row['id']) ?? new PurchaseReturn;
            $return->id = $row['id'];
            $return->fill([
                'no_retur' => $row['no_retur'],
                'purchase_id' => $row['purchase_id'],
                'user_id' => $this->resolveUserId($row['user_id'] ?? null, $fallbackUserId),
                'metode_pengembalian' => $row['metode_pengembalian'] ?? 'potong_hutang',
                'total_retur' => $row['total_retur'] ?? 0,
                'alasan' => $row['alasan'] ?? null,
                'waktu' => $row['waktu'],
                'deleted_at' => $row['deleted_at'] ?? null,
            ]);
            $return->save();

            foreach ($row['items'] ?? [] as $itemData) {
                $item = PurchaseReturnItem::find($itemData['id']) ?? new PurchaseReturnItem;
                $item->id = $itemData['id'];
                $item->fill([
                    'purchase_return_id' => $return->id,
                    'purchase_item_id' => $itemData['purchase_item_id'],
                    'product_id' => $itemData['product_id'],
                    'nama_produk' => $itemData['nama_produk'],
                    'qty' => $itemData['qty'],
                    'harga_beli' => $itemData['harga_beli'],
                    'subtotal' => $itemData['subtotal'],
                ]);
                $item->save();
            }

            $count++;
        }

        return $count;
    }
}
