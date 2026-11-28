<div class="space-y-5">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <flux:heading size="xl" level="1">Laporan Stok & Evaluasi Aset</flux:heading>
            <flux:subheading>Pemantauan nilai modal barang, potensi omzet retail, dan peringatan dini stok menipis.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button type="button" wire:click="exportCsv" variant="subtle" size="sm" icon="arrow-down-tray">
                Export Excel (.csv)
            </flux:button>
            <flux:button href="{{ route('admin.stok.penyesuaian') }}" variant="primary" size="sm" icon="adjustments-horizontal" wire:navigate>
                + Penyesuaian Stok
            </flux:button>
        </div>
    </div>

    <!-- KPI Ringkasan Nilai Aset Modal & Potensi Omzet (High-Density, Solid Neutral) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Nilai Modal Aset -->
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Modal Aset (Harga Beli)</span>
                <flux:icon name="archive-box" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                Rp {{ number_format($totalNilaiModal, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Total nilai modal stok saat ini</div>
        </div>

        <!-- Potensi Omzet Retail -->
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Potensi Omzet Retail</span>
                <flux:icon name="banknotes" class="size-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($totalNilaiRetail, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Nilai jual total persediaan</div>
        </div>

        <!-- Stok Menipis Warning -->
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Stok Menipis (&le; Min)</span>
                <flux:icon name="exclamation-triangle" class="size-4 text-amber-500" />
            </div>
            <div class="text-2xl font-bold font-mono {{ $totalMenipis > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $totalMenipis }}
            </div>
            <div class="text-[11px] text-zinc-400">Perlu restock segera</div>
        </div>

        <!-- Stok Habis Alert -->
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Stok Kosong / Habis</span>
                <flux:icon name="x-circle" class="size-4 text-rose-500" />
            </div>
            <div class="text-2xl font-bold font-mono {{ $totalHabis > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $totalHabis }}
            </div>
            <div class="text-[11px] text-zinc-400">Stok 0 di etalase & gudang</div>
        </div>
    </div>

    <!-- Filter & Pencarian (Compact) -->
    <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <!-- Search Keyword -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Cari Produk / Barcode</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Ketik nama produk atau barcode..."
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Filter Golongan -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Filter Golongan</label>
                <select
                    wire:model.live="selectedGolongan"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Semua Golongan --</option>
                    @foreach($golongans as $g)
                        <option value="{{ $g->id }}">[{{ $g->kode_golongan }}] {{ $g->nama_golongan }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status Stok -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Status Stok</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">Semua Kondisi Stok</option>
                    <option value="menipis">Stok Menipis (&le; Min Stok)</option>
                    <option value="habis">Stok Kosong (0)</option>
                    <option value="aman">Stok Aman (&gt; Min Stok)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabel Data Laporan Stok (High-Density) -->
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 uppercase text-[11px] font-semibold border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-2.5 px-3">Produk & Barcode</th>
                        <th class="py-2.5 px-3">Golongan</th>
                        <th class="py-2.5 px-3 text-right">Harga Beli</th>
                        <th class="py-2.5 px-3 text-right">Harga Retail</th>
                        <th class="py-2.5 px-3 text-center">Etalase</th>
                        <th class="py-2.5 px-3 text-center">Gudang</th>
                        <th class="py-2.5 px-3 text-center">Total Stok</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                    @forelse($products as $p)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                            <td class="py-2 px-3 font-sans">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $p->nama }}
                                </div>
                                <div class="text-[11px] font-mono text-zinc-400">
                                    {{ $p->kode_produk }} &bull; {{ $p->satuan }}
                                </div>
                            </td>
                            <td class="py-2 px-3 text-[11px] font-sans text-zinc-600 dark:text-zinc-400">
                                {{ $p->golongan->nama_golongan ?? '-' }}
                            </td>
                            <td class="py-2 px-3 text-right text-zinc-500">
                                Rp {{ number_format($p->harga_beli, 0, ',', '.') }}
                            </td>
                            <td class="py-2 px-3 text-right font-bold text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format($p->harga_retail, 0, ',', '.') }}
                            </td>
                            <td class="py-2 px-3 text-center text-zinc-600 dark:text-zinc-400">
                                {{ $p->stok_etalase }}
                            </td>
                            <td class="py-2 px-3 text-center text-zinc-600 dark:text-zinc-400">
                                {{ $p->stok_gudang }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold text-zinc-900 dark:text-zinc-100">
                                {{ $p->stok }} <span class="text-[10px] font-normal text-zinc-400">{{ $p->satuan }}</span>
                            </td>
                            <td class="py-2 px-3 text-center">
                                @if($p->stok <= 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800">[Habis]</span>
                                @elseif($p->stok <= $p->min_stok)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">[Menipis: {{ $p->min_stok }}]</span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono text-zinc-500 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">[Aman]</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-center font-sans">
                                <flux:button
                                    href="{{ route('admin.stok.penyesuaian', ['productId' => $p->id]) }}"
                                    size="xs"
                                    variant="subtle"
                                    icon="adjustments-horizontal"
                                    wire:navigate
                                >
                                    Opname
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-6 text-center text-xs text-zinc-400 font-sans">
                                Tidak ada data stok yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 font-sans">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
