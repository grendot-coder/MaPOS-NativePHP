<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Penyesuaian Stok (Stock Opname)</flux:heading>
                <flux:badge color="blue" size="sm">Admin</flux:badge>
            </div>
            <flux:subheading>Koreksi stok fisik aktual di etalase dan gudang jika terdapat selisih hitung, barang rusak, atau retur.</flux:subheading>
        </div>
        <div>
            <flux:button href="{{ route('admin.stok.riwayat') }}" variant="subtle" icon="clock" wire:navigate>
                Lihat Buku Kartu Stok
            </flux:button>
        </div>
    </div>

    <!-- Panel Pemilihan Produk (Scan Barcode & Dropdown) -->
    <flux:card class="p-5 space-y-4 border-2 border-emerald-500/20">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon name="qr-code" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">1. Pilih Produk yang Akan Disesuaikan</span>
            </div>
            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                📱 Siap Scan via Barcode to PC / Scanner USB
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
            <!-- Scan Barcode Input -->
            <form wire:submit.prevent="scanBarcode" class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Scan Barcode / Ketik Kode Produk</label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        wire:model="barcodeInput"
                        placeholder="Arahkan scan dari HP atau ketik kode..."
                        class="flex-1 font-mono text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-emerald-500"
                        autofocus
                    />
                    <flux:button type="submit" variant="primary" icon="magnifying-glass">
                        Cari
                    </flux:button>
                </div>
            </form>

            <!-- Dropdown Manual Picker -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Atau Pilih dari Daftar Produk</label>
                <select
                    wire:change="selectProduct($event.target.value)"
                    class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ $selectedProductId === $p->id ? 'selected' : '' }}>
                            {{ $p->nama }} ({{ $p->kode_produk }}) — Stok: {{ $p->stok }} {{ $p->satuan }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </flux:card>

    @if($selectedProduct)
        <!-- Form Penyesuaian Stok Produk -->
        <flux:card class="p-6 space-y-6">
            <!-- Ringkasan Produk Terpilih -->
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800 gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 font-mono text-zinc-500">
                            {{ $selectedProduct->golongan->nama_golongan ?? 'Umum' }}
                        </span>
                        <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                            {{ $selectedProduct->kode_produk }}
                        </span>
                    </div>
                    <flux:heading size="lg" class="mt-1">{{ $selectedProduct->nama }}</flux:heading>
                </div>
                <div class="flex items-center gap-4 text-center">
                    <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 min-w-24">
                        <div class="text-[11px] text-zinc-400">Etalase Sistem</div>
                        <div class="text-base font-bold text-zinc-800 dark:text-zinc-200 font-mono">
                            {{ $selectedProduct->stok_etalase }} <span class="text-xs font-normal">{{ $selectedProduct->satuan }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 min-w-24">
                        <div class="text-[11px] text-zinc-400">Gudang Sistem</div>
                        <div class="text-base font-bold text-zinc-800 dark:text-zinc-200 font-mono">
                            {{ $selectedProduct->stok_gudang }} <span class="text-xs font-normal">{{ $selectedProduct->satuan }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 min-w-24">
                        <div class="text-[11px] text-emerald-700 dark:text-emerald-300 font-semibold">Total Sistem</div>
                        <div class="text-base font-black text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ $selectedProduct->stok }} <span class="text-xs font-normal">{{ $selectedProduct->satuan }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Nilai Fisik Aktual Baru -->
            <div class="space-y-4">
                <flux:heading size="base">2. Masukkan Stok Fisik Aktual (Hasil Hitung Lapangan)</flux:heading>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Stok Etalase Baru -->
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Stok Etalase Fisik Aktual *</label>
                        <input
                            type="number"
                            min="0"
                            wire:model.live="stok_etalase_baru"
                            class="w-full text-base font-mono font-bold rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-emerald-500"
                        />
                        @php
                            $diffEtalase = (int)$stok_etalase_baru - (int)$selectedProduct->stok_etalase;
                        @endphp
                        <div class="text-xs font-mono {{ $diffEtalase > 0 ? 'text-emerald-600' : ($diffEtalase < 0 ? 'text-rose-500 font-semibold' : 'text-zinc-400') }}">
                            Selisih Etalase: {{ $diffEtalase > 0 ? "+{$diffEtalase}" : $diffEtalase }} {{ $selectedProduct->satuan }}
                        </div>
                        @error('stok_etalase_baru') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Stok Gudang Baru -->
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Stok Gudang Fisik Aktual *</label>
                        <input
                            type="number"
                            min="0"
                            wire:model.live="stok_gudang_baru"
                            class="w-full text-base font-mono font-bold rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-emerald-500"
                        />
                        @php
                            $diffGudang = (int)$stok_gudang_baru - (int)$selectedProduct->stok_gudang;
                        @endphp
                        <div class="text-xs font-mono {{ $diffGudang > 0 ? 'text-emerald-600' : ($diffGudang < 0 ? 'text-rose-500 font-semibold' : 'text-zinc-400') }}">
                            Selisih Gudang: {{ $diffGudang > 0 ? "+{$diffGudang}" : $diffGudang }} {{ $selectedProduct->satuan }}
                        </div>
                        @error('stok_gudang_baru') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Kalkulasi Total Baru & Selisih Akhir -->
                    @php
                        $totalBaru = (int)$stok_etalase_baru + (int)$stok_gudang_baru;
                        $totalSelisih = $totalBaru - (int)$selectedProduct->stok;
                    @endphp
                    <div class="p-3.5 rounded-xl {{ $totalSelisih === 0 ? 'bg-zinc-100 dark:bg-zinc-800' : ($totalSelisih > 0 ? 'bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700' : 'bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-700') }} flex flex-col justify-center">
                        <div class="text-xs font-semibold text-zinc-500">Total Stok Baru: <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200">{{ $totalBaru }} {{ $selectedProduct->satuan }}</span></div>
                        <div class="text-lg font-black font-mono mt-0.5 {{ $totalSelisih > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($totalSelisih < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-500') }}">
                            Selisih Bersih: {{ $totalSelisih > 0 ? "+{$totalSelisih}" : $totalSelisih }} {{ $selectedProduct->satuan }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alasan / Keterangan Penyesuaian -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">3. Alasan / Keterangan Penyesuaian *</label>
                <!-- Preset Buttons -->
                <div class="flex flex-wrap gap-1.5">
                    @foreach($presetKeterangan as $preset)
                        <button
                            type="button"
                            wire:click="setKeterangan('{{ $preset }}')"
                            class="px-2.5 py-1 text-xs rounded-md border {{ $keterangan === $preset ? 'bg-emerald-100 dark:bg-emerald-900 border-emerald-500 text-emerald-800 dark:text-emerald-200 font-bold' : 'bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100' }} transition"
                        >
                            {{ $preset }}
                        </button>
                    @endforeach
                </div>

                <input
                    type="text"
                    wire:model="keterangan"
                    placeholder="Tuliskan catatan alasan penyesuaian..."
                    class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                />
                @error('keterangan') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
            </div>

            <!-- Tombol Simpan -->
            <div class="flex justify-end pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button
                    type="button"
                    wire:click="saveAdjustment"
                    variant="primary"
                    class="bg-emerald-600 hover:bg-emerald-700 shadow-sm"
                    icon="check"
                >
                    Simpan Penyesuaian Stok
                </flux:button>
            </div>
        </flux:card>
    @else
        <!-- Box Kosong Belum Pilih Produk -->
        <div class="p-8 text-center rounded-xl border border-dashed border-zinc-300 dark:border-zinc-800 space-y-2">
            <div class="mx-auto size-12 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-400">
                <flux:icon name="cube" class="size-6" />
            </div>
            <div class="font-medium text-zinc-700 dark:text-zinc-300">Belum Ada Produk yang Dipilih</div>
            <div class="text-xs text-zinc-400 max-w-sm mx-auto">
                Silakan scan barcode barang dari HP (Barcode to PC) atau pilih nama produk dari kotak pilihan di atas untuk memulai penyesuaian stok.
            </div>
        </div>
    @endif

    <!-- Tabel 10 Penyesuaian Stok Terbaru -->
    <flux:card class="p-0 overflow-hidden shadow-sm">
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon name="clock" class="size-5 text-zinc-600 dark:text-zinc-400" />
                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Riwayat Penyesuaian Stok Terakhir</span>
            </div>
            <flux:button href="{{ route('admin.stok.riwayat') }}" size="sm" variant="subtle" wire:navigate>
                Lihat Semua &rarr;
            </flux:button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-semibold uppercase">
                    <tr>
                        <th class="py-2.5 px-4">Waktu</th>
                        <th class="py-2.5 px-4">Produk</th>
                        <th class="py-2.5 px-4 text-center">Perubahan</th>
                        <th class="py-2.5 px-4 text-center">Saldo Akhir</th>
                        <th class="py-2.5 px-4">Alasan / Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($recentAdjustments as $adj)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                            <td class="py-2.5 px-4 text-xs font-mono text-zinc-500 whitespace-nowrap">
                                {{ $adj->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2.5 px-4">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $adj->product->nama ?? 'Produk Dihapus' }}
                                </div>
                                <div class="text-xs font-mono text-zinc-400">
                                    {{ $adj->product->kode_produk ?? '-' }}
                                </div>
                            </td>
                            <td class="py-2.5 px-4 text-center whitespace-nowrap font-mono font-bold">
                                @if($adj->jumlah > 0)
                                    <span class="text-emerald-600">+{{ $adj->jumlah }}</span>
                                @elseif($adj->jumlah < 0)
                                    <span class="text-rose-500">{{ $adj->jumlah }}</span>
                                @else
                                    <span class="text-zinc-400">0</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-center font-mono font-bold text-zinc-800 dark:text-zinc-200">
                                {{ $adj->saldo_sesudah }}
                            </td>
                            <td class="py-2.5 px-4 text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $adj->keterangan }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-xs text-zinc-400">
                                Belum ada riwayat penyesuaian stok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
