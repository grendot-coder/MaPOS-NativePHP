<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Pengeluaran Biaya & Kasbon</flux:heading>
            <flux:subheading>Catat operasional toko (listrik, air, kresek) dan kasbon karyawan agar uang laci kasir selalu seimbang (*balance*).</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button type="button" wire:click="openCreateModal('operasional')" variant="primary" icon="plus" class="bg-amber-600 hover:bg-amber-700">
                + Biaya Operasional
            </flux:button>
            <flux:button type="button" wire:click="openCreateModal('kasbon')" variant="primary" icon="user-minus" class="bg-rose-600 hover:bg-rose-700">
                + Kasbon Karyawan
            </flux:button>
        </div>
    </div>

    <!-- 3 Kartu Statistik Ringkasan (High-Density, Solid Neutral) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Biaya Operasional Toko</span>
                <flux:icon name="bolt" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                Rp {{ number_format($totalOperasional, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Listrik, air, kantong plastik, dsb.</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Kasbon Karyawan</span>
                <flux:icon name="user-minus" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-rose-600 dark:text-rose-400">
                Rp {{ number_format($totalKasbon, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Pinjaman kasir / staf toko aktif</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Pengeluaran Bulan Ini</span>
                <flux:icon name="banknotes" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                Rp {{ number_format($totalSemua, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Periode: {{ date('F Y', strtotime($filterBulan . '-01')) }}</div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <flux:card class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[11px] font-medium text-zinc-500 mb-1">Periode Bulan:</label>
                <input type="month" wire:model.live="filterBulan" class="text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-800 dark:text-zinc-200 font-mono" />
            </div>

            <div>
                <label class="block text-[11px] font-medium text-zinc-500 mb-1">Kategori:</label>
                <select wire:model.live="filterKategori" class="text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-800 dark:text-zinc-200">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $k)
                    <option value="{{ $k->value }}">{{ $k->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="w-full md:w-72">
            <label class="block text-[11px] font-medium text-zinc-500 mb-1">Cari Keterangan / Karyawan:</label>
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Ketik kata kunci..." icon="magnifying-glass" size="sm" />
        </div>
    </flux:card>

    <!-- Tabel Riwayat Pengeluaran -->
    <flux:card class="p-0 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-700 dark:text-zinc-300">
                <thead class="bg-zinc-100 dark:bg-zinc-800/80 uppercase text-[10px] tracking-wider font-bold text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="p-3">Waktu & No. Ref</th>
                        <th class="p-3">Kategori</th>
                        <th class="p-3">Keterangan / Karyawan</th>
                        <th class="p-3">Dicatat Oleh</th>
                        <th class="p-3 text-right">Nominal</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($pengeluarans as $p)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <td class="p-3">
                            <div class="font-bold text-zinc-900 dark:text-zinc-100 font-mono">{{ $p->no_referensi }}</div>
                            <div class="text-[11px] text-zinc-400">{{ $p->waktu->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="p-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                                {{ $p->kategori->label() }}
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->keterangan }}</div>
                            @if($p->nama_karyawan)
                            <div class="text-[11px] text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1 mt-0.5">
                                <flux:icon name="user" class="size-3" />
                                Karyawan: <span class="font-bold">{{ $p->nama_karyawan }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="p-3 font-mono text-[11px] text-zinc-500">
                            {{ $p->user->name ?? 'Kasir' }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-sm text-rose-600 dark:text-rose-400">
                            - Rp {{ number_format($p->nominal, 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-center">
                            <button
                                type="button"
                                wire:click="delete('{{ $p->id }}')"
                                wire:confirm="Yakin ingin menghapus catatan pengeluaran [{{ $p->no_referensi }}]?"
                                class="p-1 rounded text-zinc-400 hover:text-rose-600 transition"
                                title="Hapus">
                                <flux:icon name="trash" class="size-4" />
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-xs text-zinc-400">
                            Belum ada catatan pengeluaran pada periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pengeluarans->hasPages())
        <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
            {{ $pengeluarans->links() }}
        </div>
        @endif
    </flux:card>

    <!-- MODAL CATAT PENGELUARAN / KASBON -->
    <flux:modal wire:model="showCreateModal" class="md:w-[480px] space-y-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon name="banknotes" class="size-6 text-amber-600 dark:text-amber-400" />
                <flux:heading size="lg">Catat Pengeluaran Uang Kas</flux:heading>
            </div>
            <flux:subheading>Uang tunai yang keluar dari laci kasir untuk operasional toko atau kasbon.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <!-- Pilihan Kategori -->
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Jenis Pengeluaran *</label>
                <select wire:model.live="kategori" class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                    <option value="operasional">Biaya Operasional Toko (Listrik, Kresek, dll)</option>
                    <option value="kasbon">Kasbon / Pinjaman Karyawan</option>
                    <option value="gaji">Penggajian / Upah Karyawan</option>
                    <option value="lainnya">Pengeluaran Lain-lain</option>
                </select>
                @error('kategori') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
            </div>

            <!-- Nama Karyawan (Kondisional Kasbon/Gaji) -->
            @if(in_array($kategori, ['kasbon', 'gaji']))
            <div>
                <label class="block text-xs font-medium text-rose-600 dark:text-rose-400 mb-1">Nama Karyawan yang Kasbon *</label>
                <input
                    type="text"
                    wire:model="nama_karyawan"
                    placeholder="Contoh: Budi (Kasir Shift Pagi)"
                    class="w-full text-sm rounded-lg border border-rose-300 dark:border-rose-700/50 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200 focus:border-rose-500" />
                @error('nama_karyawan') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
            </div>
            @endif

            <!-- Nominal Pengeluaran -->
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Nominal Uang Keluar (Rp) *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-zinc-400">Rp</span>
                    <input
                        type="number"
                        step="500"
                        wire:model="nominal"
                        placeholder="Contoh: 50000"
                        class="w-full text-sm font-mono font-bold text-rose-600 dark:text-rose-400 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-8 pr-3 py-2" />
                </div>
                @error('nominal') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Keterangan / Keperluan *</label>
                <input
                    type="text"
                    wire:model="keterangan"
                    placeholder="{{ $kategori === 'kasbon' ? 'Pinjaman uang belanja keluarga' : 'Contoh: Beli kantong kresek hitam 2 pack' }}"
                    class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                @error('keterangan') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
            </div>

            <!-- Waktu Pengeluaran -->
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Waktu Transaksi</label>
                <input
                    type="datetime-local"
                    wire:model="waktu"
                    class="w-full text-xs font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showCreateModal', false)" variant="ghost">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary" class="bg-amber-600 hover:bg-amber-700" icon="check">
                    Simpan Pengeluaran
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>