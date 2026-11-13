<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Dashboard Manajemen</flux:heading>
            <flux:subheading>Selamat datang, {{ auth()->user()->name }}. Berikut ikhtisar inventaris dan operasional MaPOS.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.produk.create') }}" variant="primary" icon="plus" wire:navigate>
                Tambah Produk
            </flux:button>
            <flux:button href="{{ route('admin.produk.index') }}" variant="filled" icon="cube" wire:navigate>
                Kelola Produk
            </flux:button>
        </div>
    </div>

    <!-- Statistik Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <flux:card class="space-y-2">
            <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                <flux:text size="sm">Total Produk</flux:text>
                <flux:icon name="cube" class="size-5 text-indigo-500" />
            </div>
            <flux:heading size="xl">{{ number_format($totalProduk) }}</flux:heading>
            <flux:text size="xs" class="text-zinc-500">Item terdaftar dalam sistem</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                <flux:text size="sm">Golongan / Kategori</flux:text>
                <flux:icon name="tag" class="size-5 text-emerald-500" />
            </div>
            <flux:heading size="xl">{{ number_format($totalGolongan) }}</flux:heading>
            <flux:text size="xs" class="text-zinc-500">Kategori produk aktif</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                <flux:text size="sm">Stok Menipis / Kritis</flux:text>
                <flux:icon name="exclamation-triangle" class="size-5 text-amber-500" />
            </div>
            <flux:heading size="xl" class="{{ $stokMenipisCount > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">
                {{ number_format($stokMenipisCount) }}
            </flux:heading>
            <flux:text size="xs" class="text-zinc-500">Stok &le; batas minimum</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                <flux:text size="sm">Estimasi Nilai Stok (Beli)</flux:text>
                <flux:icon name="banknotes" class="size-5 text-blue-500" />
            </div>
            <flux:heading size="xl">Rp {{ number_format($totalNilaiAset, 0, ',', '.') }}</flux:heading>
            <flux:text size="xs" class="text-zinc-500">Total modal persediaan barang</flux:text>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Peringatan Stok Kritis -->
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Perhatian: Stok Menipis</flux:heading>
                <flux:badge color="amber" size="sm">{{ $stokMenipisCount }} Produk</flux:badge>
            </div>
            <flux:separator />

            @if($lowStockProducts->isEmpty())
                <div class="py-8 text-center text-zinc-500 text-sm">
                    Semua stok produk saat ini dalam kondisi aman di atas batas minimum.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 uppercase">
                                <th class="pb-2">Produk</th>
                                <th class="pb-2">Golongan</th>
                                <th class="pb-2 text-right">Stok</th>
                                <th class="pb-2 text-right">Min Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($lowStockProducts as $p)
                                <tr>
                                    <td class="py-2.5 font-medium">
                                        {{ $p->nama }}
                                        <div class="text-xs text-zinc-400 font-mono">{{ $p->kode_produk }}</div>
                                    </td>
                                    <td class="py-2.5 text-zinc-500">{{ $p->golongan->nama_golongan }}</td>
                                    <td class="py-2.5 text-right font-semibold text-amber-600 dark:text-amber-400">
                                        {{ $p->stok }} {{ $p->satuan }}
                                    </td>
                                    <td class="py-2.5 text-right text-zinc-400">{{ $p->min_stok }} {{ $p->satuan }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>

        <!-- Pergerakan Stok Terkini -->
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Aktivitas Stok Terakhir</flux:heading>
                <flux:text size="sm" class="text-zinc-500">5 Transaksi Terkini</flux:text>
            </div>
            <flux:separator />

            @if($recentMovements->isEmpty())
                <div class="py-8 text-center text-zinc-500 text-sm">
                    Belum ada riwayat pergerakan stok.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentMovements as $m)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                            <div>
                                <div class="text-sm font-medium">{{ $m->product?->nama ?? 'Produk Dihapus' }}</div>
                                <div class="text-xs text-zinc-400">
                                    {{ $m->created_at->translatedFormat('d M Y, H:i') }} &bull; {{ $m->keterangan ?? $m->jenis->label() }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-semibold {{ $m->jumlah >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $m->jumlah >= 0 ? '+' : '' }}{{ $m->jumlah }}
                                </span>
                                <div class="text-xs text-zinc-400">Sisa: {{ $m->saldo_sesudah }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>
    </div>
</div>
