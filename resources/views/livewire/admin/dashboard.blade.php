<div class="space-y-5">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <flux:heading size="xl" level="1">Dashboard Manajemen</flux:heading>
            <flux:subheading>Selamat datang, {{ auth()->user()->name }}. Berikut ikhtisar inventaris dan operasional MaPOS.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.produk.create') }}" variant="primary" size="sm" icon="plus" wire:navigate>
                Tambah Produk
            </flux:button>
            <flux:button href="{{ route('admin.produk.index') }}" variant="subtle" size="sm" icon="cube" wire:navigate>
                Kelola Produk
            </flux:button>
        </div>
    </div>

    <!-- 4 Statistik Utama (High-Density, Solid Neutral) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Produk</span>
                <flux:icon name="cube" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                {{ number_format($totalProduk) }}
            </div>
            <div class="text-[11px] text-zinc-400">Item terdaftar dalam sistem</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Golongan / Kategori</span>
                <flux:icon name="tag" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                {{ number_format($totalGolongan) }}
            </div>
            <div class="text-[11px] text-zinc-400">Kategori produk aktif</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Stok Menipis / Kritis</span>
                <flux:icon name="exclamation-triangle" class="size-4 text-amber-500" />
            </div>
            <div class="text-2xl font-bold font-mono {{ $stokMenipisCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ number_format($stokMenipisCount) }}
            </div>
            <div class="text-[11px] text-zinc-400">Stok &le; batas minimum</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Estimasi Modal Stok</span>
                <flux:icon name="banknotes" class="size-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">
                Rp {{ number_format($totalNilaiAset, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Total modal persediaan barang</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Peringatan Stok Kritis (High-Density) -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm space-y-0">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-xs uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Perhatian: Stok Menipis</span>
                    @if($stokMenipisCount > 0)
                        <span class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400">({{ $stokMenipisCount }} Produk)</span>
                    @endif
                </div>
            </div>

            @if($lowStockProducts->isEmpty())
                <div class="p-6 text-center text-zinc-400 text-xs">
                    Semua stok produk saat ini dalam kondisi aman di atas batas minimum.
                </div>
            @else
                <div class="overflow-x-auto max-h-72 overflow-y-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 uppercase text-[11px] font-semibold border-b border-zinc-200 dark:border-zinc-800 sticky top-0">
                            <tr>
                                <th class="py-2 px-3">Produk</th>
                                <th class="py-2 px-3">Golongan</th>
                                <th class="py-2 px-3 text-right">Stok</th>
                                <th class="py-2 px-3 text-right">Min Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                            @foreach($lowStockProducts as $p)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                                    <td class="py-2 px-3 font-sans">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $p->nama }}</div>
                                        <div class="text-[11px] font-mono text-zinc-400">{{ $p->kode_produk }}</div>
                                    </td>
                                    <td class="py-2 px-3 font-sans text-zinc-500">{{ $p->golongan->nama_golongan }}</td>
                                    <td class="py-2 px-3 text-right font-bold text-amber-600 dark:text-amber-400">
                                        {{ $p->stok }} <span class="text-[10px] font-normal text-zinc-400">{{ $p->satuan }}</span>
                                    </td>
                                    <td class="py-2 px-3 text-right text-zinc-400">
                                        {{ $p->min_stok }} <span class="text-[10px] font-normal text-zinc-400">{{ $p->satuan }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Pergerakan Stok Terkini (High-Density) -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm space-y-0">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <span class="font-bold text-xs uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Aktivitas Stok Terakhir</span>
                <span class="text-[11px] font-mono text-zinc-400">5 Transaksi Terkini</span>
            </div>

            @if($recentMovements->isEmpty())
                <div class="p-6 text-center text-zinc-400 text-xs">
                    Belum ada riwayat pergerakan stok.
                </div>
            @else
                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach($recentMovements as $m)
                        <div class="flex items-center justify-between p-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                            <div>
                                <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ $m->product?->nama ?? 'Produk Dihapus' }}</div>
                                <div class="text-[11px] text-zinc-400 font-mono">
                                    {{ $m->created_at->translatedFormat('d M Y, H:i') }} &bull; {{ $m->keterangan ?? $m->jenis->label() }}
                                </div>
                            </div>
                            <div class="text-right font-mono">
                                <span class="text-xs font-bold {{ $m->jumlah >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $m->jumlah >= 0 ? '+' : '' }}{{ $m->jumlah }}
                                </span>
                                <div class="text-[10px] text-zinc-400">Sisa: {{ $m->saldo_sesudah }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
