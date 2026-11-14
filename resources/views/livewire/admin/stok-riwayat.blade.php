<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Buku Kartu Stok</flux:heading>
                <flux:badge color="blue" size="sm">Audit Log Mutasi</flux:badge>
            </div>
            <flux:subheading>Riwayat lengkap aliran barang masuk, keluar kasir, dan penyesuaian stok per tanggal.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.stok.penyesuaian') }}" variant="primary" icon="adjustments-horizontal" wire:navigate>
                + Penyesuaian Stok
            </flux:button>
        </div>
    </div>

    <!-- Ringkasan Statistik Mutasi -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <flux:card class="p-4 flex items-center gap-3">
            <div class="size-10 flex items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <flux:icon name="arrow-trending-up" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-zinc-500">Total Stok Masuk</div>
                <div class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">+{{ number_format($totalMasuk) }}</div>
            </div>
        </flux:card>

        <flux:card class="p-4 flex items-center gap-3">
            <div class="size-10 flex items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                <flux:icon name="arrow-trending-down" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-zinc-500">Total Stok Keluar</div>
                <div class="text-xl font-bold font-mono text-rose-600 dark:text-rose-400">-{{ number_format($totalKeluar) }}</div>
            </div>
        </flux:card>

        <flux:card class="p-4 flex items-center gap-3">
            <div class="size-10 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                <flux:icon name="document-text" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-zinc-500">Total Mutasi Tercatat</div>
                <div class="text-xl font-bold font-mono text-zinc-800 dark:text-zinc-200">{{ number_format($totalRecords) }} Log</div>
            </div>
        </flux:card>
    </div>

    <!-- Filter & Pencarian -->
    <flux:card class="p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <!-- Search Keyword -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Cari Produk / Barcode</label>
                <input
                    type="text"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Nama atau barcode..."
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Filter Produk Dropdown -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Filter Produk</label>
                <select
                    wire:model.live="selectedProduct"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Semua Produk --</option>
                    @foreach($productList as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Jenis Mutasi -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Jenis Mutasi</label>
                <select
                    wire:model.live="selectedJenis"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Semua Jenis --</option>
                    @foreach($jenisList as $j)
                        <option value="{{ $j->value }}">{{ $j->label() }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Rentang Tanggal Mulai -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Dari Tanggal</label>
                <input
                    type="date"
                    wire:model.live="startDate"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Rentang Tanggal Selesai & Reset -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-medium text-zinc-500">Sampai Tanggal</label>
                    <button type="button" wire:click="resetFilters" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline">Reset</button>
                </div>
                <input
                    type="date"
                    wire:model.live="endDate"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                />
            </div>
        </div>
    </flux:card>

    <!-- Tabel Buku Kartu Stok -->
    <flux:card class="p-0 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-semibold uppercase">
                    <tr>
                        <th class="py-3 px-4 whitespace-nowrap">Waktu</th>
                        <th class="py-3 px-4">Produk & Barcode</th>
                        <th class="py-3 px-4 text-center">Jenis Mutasi</th>
                        <th class="py-3 px-4 text-center">Perubahan</th>
                        <th class="py-3 px-4 text-center">Saldo Akhir</th>
                        <th class="py-3 px-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($movements as $m)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                            <td class="py-3 px-4 text-xs font-mono text-zinc-500 whitespace-nowrap">
                                {{ $m->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $m->product->nama ?? 'Produk Dihapus' }}
                                </div>
                                <div class="text-xs font-mono text-zinc-400 flex items-center gap-2">
                                    <span>{{ $m->product->kode_produk ?? '-' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $m->product->satuan ?? 'pcs' }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @php
                                    $badgeColor = match($m->jenis) {
                                        \App\Enums\StockMovementType::Penjualan => 'rose',
                                        \App\Enums\StockMovementType::Penyesuaian => 'amber',
                                        \App\Enums\StockMovementType::Pembelian => 'emerald',
                                        \App\Enums\StockMovementType::Retur => 'purple',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge size="sm" color="{{ $badgeColor }}">
                                    {{ $m->jenis->label() }}
                                </flux:badge>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap font-mono font-bold text-sm">
                                @if($m->jumlah > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ $m->jumlah }}</span>
                                @elseif($m->jumlah < 0)
                                    <span class="text-rose-600 dark:text-rose-400">{{ $m->jumlah }}</span>
                                @else
                                    <span class="text-zinc-400">0</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-black text-sm text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                {{ $m->saldo_sesudah }}
                            </td>
                            <td class="py-3 px-4 text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $m->keterangan }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-zinc-400">
                                <div class="size-10 mx-auto flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-400 mb-2">
                                    <flux:icon name="inbox" class="size-5" />
                                </div>
                                Tidak ada catatan mutasi stok yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
                {{ $movements->links() }}
            </div>
        @endif
    </flux:card>
</div>
