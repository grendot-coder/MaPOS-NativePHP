<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Laporan Stok & Evaluasi Aset</flux:heading>
                <flux:badge color="blue" size="sm">Audit Gudang</flux:badge>
            </div>
            <flux:subheading>Pemantauan nilai modal barang, potensi omzet retail, dan peringatan dini stok menipis (*minimum stock*).</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.stok.penyesuaian') }}" variant="primary" icon="adjustments-horizontal" wire:navigate>
                + Penyesuaian Stok
            </flux:button>
        </div>
    </div>

    <!-- KPI Ringkasan Nilai Aset Modal & Potensi Omzet -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Nilai Modal Aset -->
        <flux:card class="p-4 bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 flex items-center gap-3">
            <div class="size-11 flex items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                <flux:icon name="archive-box" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-zinc-500 font-medium">Estimasi Modal Aset (Beli)</div>
                <div class="text-xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                    Rp {{ number_format($totalNilaiModal, 0, ',', '.') }}
                </div>
            </div>
        </flux:card>

        <!-- Potensi Omzet Retail -->
        <flux:card class="p-4 bg-emerald-50/50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center gap-3">
            <div class="size-11 flex items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                <flux:icon name="banknotes" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-emerald-800 dark:text-emerald-300 font-medium">Potensi Omzet Retail</div>
                <div class="text-xl font-black font-mono text-emerald-700 dark:text-emerald-300">
                    Rp {{ number_format($totalNilaiRetail, 0, ',', '.') }}
                </div>
            </div>
        </flux:card>

        <!-- Stok Menipis Warning -->
        <flux:card class="p-4 flex items-center gap-3 border {{ $totalMenipis > 0 ? 'bg-amber-50/60 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700' : '' }}">
            <div class="size-11 flex items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                <flux:icon name="exclamation-triangle" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-amber-900 dark:text-amber-300 font-medium">Stok Menipis (<= Min)</div>
                <div class="text-xl font-bold font-mono text-amber-700 dark:text-amber-400">
                    {{ $totalMenipis }} Produk
                </div>
            </div>
        </flux:card>

        <!-- Stok Habis Alert -->
        <flux:card class="p-4 flex items-center gap-3 border {{ $totalHabis > 0 ? 'bg-rose-50/60 dark:bg-rose-950/40 border-rose-300 dark:border-rose-700' : '' }}">
            <div class="size-11 flex items-center justify-center rounded-xl bg-rose-600 text-white shadow-sm">
                <flux:icon name="x-circle" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-rose-900 dark:text-rose-300 font-medium">Stok Kosong / Habis</div>
                <div class="text-xl font-bold font-mono text-rose-700 dark:text-rose-400">
                    {{ $totalHabis }} Produk
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filter & Pencarian -->
    <flux:card class="p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <!-- Search Keyword -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Cari Produk / Barcode</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Ketik nama produk atau barcode..."
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Filter Golongan -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Filter Golongan</label>
                <select
                    wire:model.live="selectedGolongan"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Semua Golongan --</option>
                    @foreach($golongans as $g)
                        <option value="{{ $g->id }}">[{{ $g->kode_golongan }}] {{ $g->nama_golongan }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status Stok -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Status Stok</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">Semua Kondisi Stok</option>
                    <option value="menipis">⚠️ Stok Menipis (&lt;= Min Stok)</option>
                    <option value="habis">❌ Stok Kosong (0)</option>
                    <option value="aman">✅ Stok Aman (&gt; Min Stok)</option>
                </select>
            </div>
        </div>
    </flux:card>

    <!-- Tabel Data Laporan Stok -->
    <flux:card class="p-0 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-semibold uppercase">
                    <tr>
                        <th class="py-3 px-4">Produk & Barcode</th>
                        <th class="py-3 px-4">Golongan</th>
                        <th class="py-3 px-4 text-right">Harga Beli</th>
                        <th class="py-3 px-4 text-right">Harga Retail</th>
                        <th class="py-3 px-4 text-center">Etalase</th>
                        <th class="py-3 px-4 text-center">Gudang</th>
                        <th class="py-3 px-4 text-center">Total Stok</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($products as $p)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                            <td class="py-3 px-4">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $p->nama }}
                                </div>
                                <div class="text-xs font-mono text-zinc-400">
                                    {{ $p->kode_produk }} &bull; {{ $p->satuan }}
                                </div>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                {{ $p->golongan->nama_golongan ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-xs whitespace-nowrap">
                                Rp {{ number_format($p->harga_beli, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-xs font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                Rp {{ number_format($p->harga_retail, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-xs">
                                {{ $p->stok_etalase }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-xs">
                                {{ $p->stok_gudang }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-black text-sm whitespace-nowrap">
                                {{ $p->stok }} <span class="text-xs font-normal text-zinc-400">{{ $p->satuan }}</span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($p->stok <= 0)
                                    <flux:badge size="sm" color="rose">Habis</flux:badge>
                                @elseif($p->stok <= $p->min_stok)
                                    <flux:badge size="sm" color="amber">Menipis (Min: {{ $p->min_stok }})</flux:badge>
                                @else
                                    <flux:badge size="sm" color="emerald">Aman</flux:badge>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <flux:button
                                    href="{{ route('admin.stok.penyesuaian', ['productId' => $p->id]) }}"
                                    size="sm"
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
                            <td colspan="9" class="py-12 text-center text-zinc-400">
                                Tidak ada data stok yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
                {{ $products->links() }}
            </div>
        @endif
    </flux:card>
</div>
