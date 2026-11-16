<div class="space-y-5">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <flux:heading size="xl" level="1">Penyesuaian Stok (Stock Opname)</flux:heading>
            <flux:subheading>Koreksi stok fisik aktual di etalase dan gudang jika terdapat selisih hitung, barang rusak, atau retur.</flux:subheading>
        </div>
        <div>
            <flux:button href="{{ route('admin.stok.riwayat') }}" variant="subtle" size="sm" icon="clock" wire:navigate>
                Lihat Buku Kartu Stok
            </flux:button>
        </div>
    </div>

    <!-- Panel Pemilihan Produk (Scan Barcode & Dropdown) -->
    <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <span class="font-bold text-xs uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
                <flux:icon name="qr-code" class="size-4 text-emerald-600 dark:text-emerald-400" />
                <span>1. Pilih Produk yang Akan Disesuaikan</span>
            </span>
            <span class="text-[11px] font-mono text-zinc-400">Scanner USB / Barcode to PC aktif</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
            <!-- Scan Barcode Input -->
            <form wire:submit.prevent="scanBarcode" class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Scan Barcode / Ketik Kode Produk</label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        wire:model="barcodeInput"
                        placeholder="Scan barcode barang atau ketik kode..."
                        class="flex-1 font-mono text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-500 focus:bg-white dark:focus:bg-zinc-900"
                        autofocus
                    />
                    <flux:button type="submit" variant="primary" size="sm" icon="magnifying-glass">
                        Cari
                    </flux:button>
                </div>
            </form>

            <!-- Dropdown Manual Picker -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Atau Pilih dari Daftar Produk</label>
                <select
                    wire:change="selectProduct($event.target.value)"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
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
    </div>

    @if($selectedProduct)
        <!-- Form Penyesuaian Stok Produk -->
        <div class="p-5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-5">
            <!-- Ringkasan Produk Terpilih -->
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800 gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-mono">
                        <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                            {{ $selectedProduct->golongan->nama_golongan ?? 'Umum' }}
                        </span>
                        <span class="text-zinc-900 dark:text-zinc-100 font-bold">
                            {{ $selectedProduct->kode_produk }}
                        </span>
                    </div>
                    <div class="text-base font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                        {{ $selectedProduct->nama }}
                    </div>
                </div>
                <div class="flex items-center gap-3 text-center">
                    <div class="p-2.5 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/60 min-w-24">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Etalase Sistem</div>
                        <div class="text-sm font-bold text-zinc-800 dark:text-zinc-200 font-mono mt-0.5">
                            {{ $selectedProduct->stok_etalase }} <span class="text-xs font-normal text-zinc-500">{{ $selectedProduct->satuan }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/60 min-w-24">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Gudang Sistem</div>
                        <div class="text-sm font-bold text-zinc-800 dark:text-zinc-200 font-mono mt-0.5">
                            {{ $selectedProduct->stok_gudang }} <span class="text-xs font-normal text-zinc-500">{{ $selectedProduct->satuan }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded border border-zinc-300 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-800 min-w-24">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">Total Sistem</div>
                        <div class="text-base font-black text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">
                            {{ $selectedProduct->stok }} <span class="text-xs font-normal text-zinc-500">{{ $selectedProduct->satuan }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Nilai Fisik Aktual Baru -->
            <div class="space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 block">
                    2. Masukkan Stok Fisik Aktual (Hasil Hitung Lapangan)
                </span>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Stok Etalase Baru -->
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Stok Etalase Fisik Aktual *</label>
                        <input
                            type="number"
                            min="0"
                            wire:model.live="stok_etalase_baru"
                            class="w-full text-base font-mono font-bold rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-500"
                        />
                        @php
                            $diffEtalase = (int)$stok_etalase_baru - (int)$selectedProduct->stok_etalase;
                        @endphp
                        <div class="text-[11px] font-mono {{ $diffEtalase > 0 ? 'text-emerald-600 font-semibold' : ($diffEtalase < 0 ? 'text-rose-600 font-semibold' : 'text-zinc-400') }}">
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
                            class="w-full text-base font-mono font-bold rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-500"
                        />
                        @php
                            $diffGudang = (int)$stok_gudang_baru - (int)$selectedProduct->stok_gudang;
                        @endphp
                        <div class="text-[11px] font-mono {{ $diffGudang > 0 ? 'text-emerald-600 font-semibold' : ($diffGudang < 0 ? 'text-rose-600 font-semibold' : 'text-zinc-400') }}">
                            Selisih Gudang: {{ $diffGudang > 0 ? "+{$diffGudang}" : $diffGudang }} {{ $selectedProduct->satuan }}
                        </div>
                        @error('stok_gudang_baru') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Kalkulasi Total Baru & Selisih Akhir -->
                    @php
                        $totalBaru = (int)$stok_etalase_baru + (int)$stok_gudang_baru;
                        $totalSelisih = $totalBaru - (int)$selectedProduct->stok;
                    @endphp
                    <div class="p-3 rounded border {{ $totalSelisih === 0 ? 'bg-zinc-100 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700' : ($totalSelisih > 0 ? 'bg-zinc-50 dark:bg-zinc-800/80 border-emerald-500/50' : 'bg-zinc-50 dark:bg-zinc-800/80 border-rose-500/50') }} flex flex-col justify-center">
                        <div class="text-[11px] font-semibold text-zinc-500">Total Stok Baru: <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200">{{ $totalBaru }} {{ $selectedProduct->satuan }}</span></div>
                        <div class="text-base font-black font-mono mt-0.5 {{ $totalSelisih > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($totalSelisih < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-500') }}">
                            Selisih Bersih: {{ $totalSelisih > 0 ? "+{$totalSelisih}" : $totalSelisih }} {{ $selectedProduct->satuan }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alasan / Keterangan Penyesuaian -->
            <div class="space-y-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">3. Alasan / Keterangan Penyesuaian *</label>
                <!-- Preset Buttons -->
                <div class="flex flex-wrap gap-1.5">
                    @foreach($presetKeterangan as $preset)
                        <button
                            type="button"
                            wire:click="setKeterangan('{{ $preset }}')"
                            class="px-2.5 py-1 text-xs font-mono rounded border {{ $keterangan === $preset ? 'bg-zinc-800 text-white dark:bg-zinc-100 dark:text-zinc-900 border-zinc-900 dark:border-zinc-100 font-bold' : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200' }} transition"
                        >
                            {{ $preset }}
                        </button>
                    @endforeach
                </div>

                <input
                    type="text"
                    wire:model="keterangan"
                    placeholder="Tuliskan catatan alasan penyesuaian..."
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-500"
                />
                @error('keterangan') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
            </div>

            <!-- Tombol Simpan -->
            <div class="flex justify-end pt-3 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button
                    type="button"
                    wire:click="saveAdjustment"
                    variant="primary"
                    size="sm"
                    class="bg-emerald-600 hover:bg-emerald-700 shadow-sm"
                    icon="check"
                >
                    Simpan Penyesuaian Stok
                </flux:button>
            </div>
        </div>
    @else
        <!-- Box Kosong Belum Pilih Produk (Clean Minimalist) -->
        <div class="p-6 text-center rounded-lg border border-dashed border-zinc-300 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-1">
            <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Belum Ada Produk yang Dipilih</div>
            <div class="text-xs text-zinc-400 max-w-sm mx-auto">
                Silakan scan barcode barang dari HP / scanner USB atau pilih produk dari dropdown di atas untuk memulai opname.
            </div>
        </div>
    @endif

    <!-- Tabel 10 Penyesuaian Stok Terbaru (High-Density) -->
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon name="clock" class="size-4 text-zinc-500" />
                <span class="font-bold text-xs uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Riwayat Penyesuaian Stok Terakhir</span>
            </div>
            <flux:button href="{{ route('admin.stok.riwayat') }}" size="xs" variant="subtle" wire:navigate>
                Lihat Semua &rarr;
            </flux:button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 uppercase text-[11px] font-semibold border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3">Produk</th>
                        <th class="py-2.5 px-3 text-center">Perubahan</th>
                        <th class="py-2.5 px-3 text-center">Saldo Akhir</th>
                        <th class="py-2.5 px-3">Alasan / Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                    @forelse($recentAdjustments as $adj)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                            <td class="py-2 px-3 text-[11px] text-zinc-500">
                                {{ $adj->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2 px-3 font-sans">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $adj->product->nama ?? 'Produk Dihapus' }}
                                </div>
                                <div class="text-[11px] font-mono text-zinc-400">
                                    {{ $adj->product->kode_produk ?? '-' }}
                                </div>
                            </td>
                            <td class="py-2 px-3 text-center font-bold">
                                @if($adj->jumlah > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ $adj->jumlah }}</span>
                                @elseif($adj->jumlah < 0)
                                    <span class="text-rose-600 dark:text-rose-400">{{ $adj->jumlah }}</span>
                                @else
                                    <span class="text-zinc-400">0</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-center font-bold text-zinc-900 dark:text-zinc-100">
                                {{ $adj->saldo_sesudah }}
                            </td>
                            <td class="py-2 px-3 text-xs font-sans text-zinc-600 dark:text-zinc-400">
                                {{ $adj->keterangan }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5 text-center text-xs text-zinc-400 font-sans">
                                Belum ada riwayat penyesuaian stok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
