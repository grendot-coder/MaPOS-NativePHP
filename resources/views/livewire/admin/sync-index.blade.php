<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Sinkronisasi REST API Server</flux:heading>
            <flux:subheading>Sinkronisasi dua arah antara SQLite lokal dan server pusat (MySQL / Web) melalui REST API.</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button type="button" wire:click="testConnection" variant="subtle" icon="signal" wire:loading.attr="disabled">
                Test Koneksi
            </flux:button>
            <flux:button type="button" wire:click="runFullSync" variant="primary" icon="arrow-path" wire:loading.attr="disabled">
                Sinkronisasi Penuh
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:card class="p-4 space-y-1.5">
            <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Status & Endpoint Server</div>
            <div class="text-sm font-mono font-bold text-zinc-900 dark:text-zinc-100 break-all">{{ $serverUrl }}</div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2 py-0.5 rounded font-semibold {{ $serverOnline === true ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($serverOnline === false ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300') }}">
                    {{ $serverOnline === true ? 'ONLINE' : ($serverOnline === false ? 'OFFLINE / GAGAL' : 'BELUM DICEK') }}
                </span>
                <span class="text-zinc-500 font-mono">{{ $branchName }} ({{ $branchId }})</span>
            </div>
        </flux:card>

        <flux:card class="p-4 space-y-1.5">
            <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Antrean Transaksi Penjualan</div>
            <div class="text-2xl font-black font-mono {{ $unsyncedSalesCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ number_format($unsyncedSalesCount) }} Nota Pending
            </div>
            <div class="text-xs text-zinc-500 font-mono">
                {{ number_format($syncedSalesCount) }} transaksi sudah tersinkron &bull; Driver lokal: {{ strtoupper($dbDriver) }}
            </div>
        </flux:card>

        <flux:card class="p-4 space-y-2">
            <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Aksi Sinkronisasi Parsial</div>
            <div class="flex flex-wrap gap-2">
                <flux:button type="button" wire:click="pushPendingSales" size="xs" variant="subtle" icon="arrow-up-tray" wire:loading.attr="disabled">
                    Push Data & Penjualan
                </flux:button>
                <flux:button type="button" wire:click="pullMasterCatalog" size="xs" variant="subtle" icon="arrow-down-tray" wire:loading.attr="disabled">
                    Pull Katalog & Master
                </flux:button>
            </div>
            @if($lastServerTime)
            <div class="text-[11px] font-mono text-zinc-400">Waktu Server: {{ $lastServerTime }}</div>
            @endif
        </flux:card>
    </div>

    @if($lastSyncSummary || $lastStatusMessage)
    <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/60 text-xs space-y-1">
        <div class="font-bold text-zinc-800 dark:text-zinc-200">Log Aktivitas Terakhir</div>
        @if($lastStatusMessage)
        <div class="text-zinc-600 dark:text-zinc-400 font-mono">{{ $lastStatusMessage }}</div>
        @endif
        @if($lastSyncSummary)
        <div class="text-zinc-800 dark:text-zinc-200 font-medium">{{ $lastSyncSummary }}</div>
        @endif
    </div>
    @endif

    <flux:card class="p-4 space-y-3">
        <div class="text-xs font-bold uppercase tracking-wider text-zinc-500">Cakupan Data Lokal Siap Sinkron</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-xs">
            <div class="p-2.5 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                <div class="text-zinc-500">Produk</div>
                <div class="text-lg font-mono font-bold">{{ $counts['products'] }}</div>
            </div>
            <div class="p-2.5 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                <div class="text-zinc-500">Member & Dropshipper</div>
                <div class="text-lg font-mono font-bold">{{ $counts['customers'] + $counts['dropshippers'] }}</div>
            </div>
            <div class="p-2.5 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                <div class="text-zinc-500">Shift Kasir</div>
                <div class="text-lg font-mono font-bold">{{ $counts['cash_shifts'] }}</div>
            </div>
            <div class="p-2.5 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                <div class="text-zinc-500">Pembelian & Retur</div>
                <div class="text-lg font-mono font-bold">{{ $counts['purchases'] + $counts['returns'] }}</div>
            </div>
            <div class="p-2.5 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                <div class="text-zinc-500">Payroll & Pengeluaran</div>
                <div class="text-lg font-mono font-bold">{{ $counts['payrolls'] + $counts['pengeluarans'] }}</div>
            </div>
        </div>
    </flux:card>

    <flux:card class="p-4 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Backup Database SQLite Lokal</div>
                <div class="text-xs text-zinc-500 font-mono break-all">
                    File aktif: {{ $sqlitePath }}
                    @if($sqliteSizeKb !== null)
                    &bull; {{ number_format($sqliteSizeKb, 1) }} KB &bull; Diperbarui {{ $sqliteModifiedAt }}
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:button type="button" wire:click="createLocalBackup" variant="subtle" size="sm" icon="archive-box">
                    Buat Snapshot Lokal
                </flux:button>
                <flux:button type="button" wire:click="downloadSqliteBackup" variant="primary" size="sm" icon="arrow-down-tray">
                    Download Backup (.sqlite)
                </flux:button>
            </div>
        </div>

        @if(!empty($backups))
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-[11px] uppercase text-zinc-500 border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-2 pr-3">Nama File Snapshot</th>
                        <th class="py-2 px-3">Waktu Dibuat</th>
                        <th class="py-2 px-3 text-right">Ukuran</th>
                        <th class="py-2 pl-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                    @foreach($backups as $b)
                    <tr>
                        <td class="py-2 pr-3 font-semibold text-zinc-800 dark:text-zinc-200">{{ $b['name'] }}</td>
                        <td class="py-2 px-3 text-zinc-500">{{ $b['modified_at'] }}</td>
                        <td class="py-2 px-3 text-right">{{ number_format($b['size_kb'], 1) }} KB</td>
                        <td class="py-2 pl-3 text-right font-sans space-x-1">
                            <flux:button type="button" wire:click="downloadSavedBackup('{{ $b['name'] }}')" size="xs" variant="subtle" icon="arrow-down-tray">Unduh</flux:button>
                            <flux:button type="button" wire:click="deleteBackup('{{ $b['name'] }}')" wire:confirm="Hapus snapshot {{ $b['name'] }}?" size="xs" variant="danger" icon="trash" />
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-xs text-zinc-400">Belum ada snapshot backup lokal di folder <span class="font-mono">storage/app/backups</span>.</div>
        @endif
    </flux:card>
</div>
