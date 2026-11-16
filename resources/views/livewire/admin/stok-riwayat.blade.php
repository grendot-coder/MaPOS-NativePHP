<div class="space-y-5">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <flux:heading size="xl" level="1">Buku Kartu Stok</flux:heading>
            <flux:subheading>Riwayat lengkap aliran barang masuk, keluar kasir, dan penyesuaian stok per tanggal.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.stok.penyesuaian') }}" variant="primary" size="sm" icon="adjustments-horizontal" wire:navigate>
                + Penyesuaian Stok
            </flux:button>
        </div>
    </div>

    <!-- Ringkasan Statistik Mutasi (High-Density, Solid Neutral) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Stok Masuk</span>
                <flux:icon name="arrow-trending-up" class="size-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                +{{ number_format($totalMasuk) }}
            </div>
            <div class="text-[11px] text-zinc-400">Saldo awal & koreksi tambah</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Stok Keluar</span>
                <flux:icon name="arrow-trending-down" class="size-4 text-rose-600 dark:text-rose-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-rose-600 dark:text-rose-400">
                -{{ number_format($totalKeluar) }}
            </div>
            <div class="text-[11px] text-zinc-400">Penjualan kasir & koreksi kurang</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Mutasi Tercatat</span>
                <flux:icon name="document-text" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                {{ number_format($totalRecords) }}
            </div>
            <div class="text-[11px] text-zinc-400">Log transaksi tersimpan</div>
        </div>
    </div>

    <!-- Filter & Pencarian (Compact) -->
    <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <!-- Search Keyword -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Cari Produk / Barcode</label>
                <input
                    type="text"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Nama atau barcode..."
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Filter Produk Dropdown -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Filter Produk</label>
                <select
                    wire:model.live="selectedProduct"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Semua Produk --</option>
                    @foreach($productList as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Jenis Mutasi -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Jenis Mutasi</label>
                <select
                    wire:model.live="selectedJenis"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Semua Jenis --</option>
                    @foreach($jenisList as $j)
                        <option value="{{ $j->value }}">{{ $j->label() }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Rentang Tanggal Mulai -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Dari Tanggal</label>
                <input
                    type="date"
                    wire:model.live="startDate"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Rentang Tanggal Selesai & Reset -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-[11px] font-medium text-zinc-500">Sampai Tanggal</label>
                    <button type="button" wire:click="resetFilters" class="text-[10px] text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 underline font-mono">Reset</button>
                </div>
                <input
                    type="date"
                    wire:model.live="endDate"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                />
            </div>
        </div>
    </div>

    <!-- Tabel Buku Kartu Stok (High-Density) -->
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 uppercase text-[11px] font-semibold border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3">Produk & Barcode</th>
                        <th class="py-2.5 px-3 text-center">Jenis Mutasi</th>
                        <th class="py-2.5 px-3 text-center">Perubahan</th>
                        <th class="py-2.5 px-3 text-center">Saldo Akhir</th>
                        <th class="py-2.5 px-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                    @forelse($movements as $m)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                            <td class="py-2 px-3 text-[11px] text-zinc-500">
                                {{ $m->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-2 px-3 font-sans">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $m->product->nama ?? 'Produk Dihapus' }}
                                </div>
                                <div class="text-[11px] font-mono text-zinc-400 flex items-center gap-2">
                                    <span>{{ $m->product->kode_produk ?? '-' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $m->product->satuan ?? 'pcs' }}</span>
                                </div>
                            </td>
                            <td class="py-2 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                                    [{{ $m->jenis->label() }}]
                                </span>
                            </td>
                            <td class="py-2 px-3 text-center font-bold">
                                @if($m->jumlah > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ $m->jumlah }}</span>
                                @elseif($m->jumlah < 0)
                                    <span class="text-rose-600 dark:text-rose-400">{{ $m->jumlah }}</span>
                                @else
                                    <span class="text-zinc-400">0</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-center font-black text-zinc-900 dark:text-zinc-100">
                                {{ $m->saldo_sesudah }}
                            </td>
                            <td class="py-2 px-3 text-xs font-sans text-zinc-600 dark:text-zinc-400">
                                {{ $m->keterangan }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-xs text-zinc-400 font-sans">
                                Tidak ada catatan mutasi stok yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 font-sans">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
