<?php

namespace App\Livewire\Admin;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Dropshipper;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Pengeluaran;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SyncService;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.app')]
#[Title('Sinkronisasi Server - MaPOS')]
class SyncIndex extends Component
{
    public ?bool $serverOnline = null;

    public string $lastStatusMessage = '';

    public ?string $lastServerTime = null;

    public string $lastSyncSummary = '';

    public function testConnection(SyncService $syncService): void
    {
        $result = $syncService->ping();
        $this->serverOnline = $result['success'];
        $this->lastStatusMessage = $result['message'];
        $this->lastServerTime = $result['server_time'];

        try {
            Flux::toast(
                variant: $result['success'] ? 'success' : 'danger',
                text: $result['success'] ? 'Server Sinkronisasi Terhubung.' : $result['message']
            );
        } catch (\Throwable) {
        }
    }

    public function pushPendingSales(SyncService $syncService): void
    {
        $batch = $syncService->pushAllOperational();
        $sales = $syncService->pushSales(200);

        $ok = $batch['success'] && $sales['success'];
        $this->serverOnline = $ok ? true : $this->serverOnline;
        $this->lastSyncSummary = $ok
            ? "Push selesai: {$sales['count']} transaksi penjualan dikirim beserta data shift, master, pembelian, retur, dan penggajian."
            : trim($batch['message'].' '.$sales['message']);

        try {
            Flux::toast(variant: $ok ? 'success' : 'danger', text: $this->lastSyncSummary);
        } catch (\Throwable) {
        }
    }

    public function pullMasterCatalog(SyncService $syncService): void
    {
        $result = $syncService->pullCatalog();

        $this->serverOnline = $result['success'] ? true : $this->serverOnline;
        $this->lastSyncSummary = $result['success']
            ? "Pull selesai: {$result['count']} produk & data master diperbarui dari server."
            : $result['message'];

        try {
            Flux::toast(variant: $result['success'] ? 'success' : 'danger', text: $this->lastSyncSummary);
        } catch (\Throwable) {
        }
    }

    public function runFullSync(SyncService $syncService): void
    {
        $result = $syncService->syncAll();

        $this->serverOnline = $result['success'] ? true : $this->serverOnline;
        $this->lastSyncSummary = $result['message'];

        try {
            Flux::toast(variant: $result['success'] ? 'success' : 'danger', text: $result['message']);
        } catch (\Throwable) {
        }
    }

    public function createLocalBackup(): ?string
    {
        $dbPath = $this->resolveSqliteDatabasePath();
        if (! $dbPath || $dbPath === ':memory:' || ! file_exists($dbPath)) {
            $msg = 'Database aktif bukan file SQLite lokal yang dapat di-backup.';
            $this->lastSyncSummary = $msg;

            try {
                Flux::toast(variant: 'warning', text: $msg);
            } catch (\Throwable) {
            }

            return null;
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $filename = 'mapos-backup-'.now()->format('Ymd-His').'.sqlite';
        $destPath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $this->exportSqliteSnapshot($dbPath, $destPath);

        $this->lastSyncSummary = "Backup SQLite berhasil dibuat: {$filename}";

        try {
            Flux::toast(variant: 'success', text: "Snapshot backup [{$filename}] berhasil disimpan.");
        } catch (\Throwable) {
        }

        return $destPath;
    }

    public function downloadSqliteBackup(): ?BinaryFileResponse
    {
        $destPath = $this->createLocalBackup();
        if (! $destPath || ! file_exists($destPath)) {
            return null;
        }

        return response()->download($destPath, basename($destPath));
    }

    public function downloadSavedBackup(string $filename): ?BinaryFileResponse
    {
        $safeName = basename($filename);
        if (! str_ends_with($safeName, '.sqlite')) {
            return null;
        }

        $path = storage_path('app/backups'.DIRECTORY_SEPARATOR.$safeName);
        if (! file_exists($path)) {
            return null;
        }

        return response()->download($path, $safeName);
    }

    public function deleteBackup(string $filename): void
    {
        $safeName = basename($filename);
        if (! str_ends_with($safeName, '.sqlite')) {
            return;
        }

        $path = storage_path('app/backups'.DIRECTORY_SEPARATOR.$safeName);
        if (file_exists($path)) {
            File::delete($path);
            $this->lastSyncSummary = "File backup [{$safeName}] dihapus.";
        }
    }

    protected function resolveSqliteDatabasePath(): ?string
    {
        $defaultConn = (string) config('database.default');
        if (config("database.connections.{$defaultConn}.driver") !== 'sqlite') {
            return null;
        }

        return config("database.connections.{$defaultConn}.database");
    }

    protected function exportSqliteSnapshot(string $sourcePath, string $destPath): void
    {
        try {
            $escaped = str_replace("'", "''", str_replace('\\', '/', $destPath));
            DB::statement("VACUUM INTO '{$escaped}'");
        } catch (\Throwable) {
            File::copy($sourcePath, $destPath);
        }
    }

    public function render()
    {
        $dbPath = $this->resolveSqliteDatabasePath();
        $dbFileExists = $dbPath && $dbPath !== ':memory:' && file_exists($dbPath);
        $backupDir = storage_path('app/backups');
        $backups = [];

        if (is_dir($backupDir)) {
            foreach (File::files($backupDir) as $file) {
                if ($file->getExtension() !== 'sqlite') {
                    continue;
                }

                $backups[] = [
                    'name' => $file->getFilename(),
                    'size_kb' => round($file->getSize() / 1024, 1),
                    'modified_at' => date('d/m/Y H:i', $file->getMTime()),
                ];
            }

            usort($backups, fn (array $a, array $b): int => strcmp($b['name'], $a['name']));
        }

        return view('livewire.admin.sync-index', [
            'serverUrl' => config('sync.server_url'),
            'branchId' => config('sync.branch_id'),
            'branchName' => config('sync.branch_name'),
            'dbDriver' => config('database.default'),
            'sqlitePath' => $dbPath ?: '-',
            'sqliteSizeKb' => $dbFileExists ? round(filesize($dbPath) / 1024, 1) : null,
            'sqliteModifiedAt' => $dbFileExists ? date('d/m/Y H:i', filemtime($dbPath)) : null,
            'backups' => array_slice($backups, 0, 10),
            'unsyncedSalesCount' => Sale::whereNull('synced_at')->count(),
            'syncedSalesCount' => Sale::whereNotNull('synced_at')->count(),
            'counts' => [
                'products' => Product::count(),
                'customers' => Customer::count(),
                'dropshippers' => Dropshipper::count(),
                'employees' => Employee::count(),
                'cash_shifts' => CashShift::count(),
                'purchases' => Purchase::count(),
                'returns' => SaleReturn::count() + PurchaseReturn::count(),
                'payrolls' => Payroll::count(),
                'pengeluarans' => Pengeluaran::count(),
            ],
        ]);
    }
}
