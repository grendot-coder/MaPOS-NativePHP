<div class="space-y-5">
    <!-- CSS Cetak Struk Khusus (@media print fallback) -->
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            dialog, [data-flux-modal], flux-modal, [popover] {
                position: static !important;
                inset: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
                overflow: visible !important;
                max-height: none !important;
                max-width: none !important;
                width: auto !important;
            }
            #laporan-receipt-print-area, #laporan-receipt-print-area * {
                visibility: visible !important;
            }
            #laporan-receipt-print-area {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 !important;
                padding: 4mm 2mm !important;
                border: none !important;
                box-shadow: none !important;
                background: white !important;
                color: black !important;
                font-family: 'Courier New', Courier, monospace !important;
                font-size: 11px !important;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>

    <!-- Header Halaman -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <flux:heading size="xl" level="1">Laporan Penjualan</flux:heading>
            <flux:subheading>Rekapitulasi penjualan kasir, total pendapatan, rincian nota, dan cetak ulang struk belanja.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button type="button" wire:click="exportCsv" variant="primary" size="sm" icon="arrow-down-tray">
                Export Excel (.csv)
            </flux:button>
            <flux:button href="{{ route('kasir.pos') }}" variant="subtle" size="sm" icon="shopping-cart" wire:navigate>
                Buka Kasir POS
            </flux:button>
        </div>
    </div>

    <!-- KPI Ringkasan Statistik (High-Density, Solid Neutral) -->
    <div class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Total Omzet -->
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Omzet Penjualan</span>
                <flux:icon name="banknotes" class="size-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">
                Rp {{ number_format($totalOmzet, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Pendapatan kotor kasir</div>
        </div>

        <!-- Total Transaksi -->
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Transaksi (Nota)</span>
                <flux:icon name="receipt-percent" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                {{ number_format($totalTransaksi) }}
            </div>
            <div class="text-[11px] text-zinc-400">Struk / nota belanja terbit</div>
        </div>

        <!-- Total Item Terjual -->
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Barang Terjual</span>
                <flux:icon name="shopping-bag" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                {{ number_format($totalItemTerjual) }}
            </div>
            <div class="text-[11px] text-zinc-400">Pcs / kemasan barang</div>
        </div>

        <!-- Rata-rata Belanja -->
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Rata-Rata per Transaksi</span>
                <flux:icon name="calculator" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                Rp {{ number_format($rataRataBelanja, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Basket size per pelanggan</div>
        </div>
    </div>

    <!-- Analitik Lanjutan: Jam Sibuk Kasir & Top 10 Produk Terlaris -->
    <div class="no-print grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Jam Sibuk Kasir -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <span class="font-bold text-xs uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Jam Sibuk Kasir (Peak Hours)</span>
                <span class="text-[11px] font-mono text-zinc-400">Berdasarkan jumlah nota</span>
            </div>
            <div class="p-3.5 space-y-2 max-h-64 overflow-y-auto">
                @forelse($peakHours as $ph)
                    @php
                        $pct = $maxHourCount > 0 ? round(($ph['count'] / $maxHourCount) * 100) : 0;
                        $isPeak = $maxHourCount > 0 && $ph['count'] === $maxHourCount;
                    @endphp
                    <div class="flex items-center gap-3 text-xs font-mono">
                        <span class="w-12 text-zinc-600 dark:text-zinc-400 font-bold">{{ $ph['label'] }}</span>
                        <div class="flex-1 h-5 bg-zinc-100 dark:bg-zinc-800 rounded overflow-hidden">
                            <div class="h-full {{ $isPeak ? 'bg-indigo-600 dark:bg-indigo-500' : 'bg-zinc-400 dark:bg-zinc-600' }} transition-all" style="width: {{ max(6, $pct) }}%"></div>
                        </div>
                        <span class="w-16 text-right font-bold {{ $isPeak ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-700 dark:text-zinc-300' }}">{{ $ph['count'] }} nota</span>
                        <span class="w-24 text-right text-zinc-500">Rp {{ number_format($ph['omzet'], 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-zinc-400">Belum ada data jam sibuk pada periode ini.</div>
                @endforelse
            </div>
        </div>

        <!-- Top 10 Produk Terlaris -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <span class="font-bold text-xs uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Top 10 Produk Terlaris</span>
                <span class="text-[11px] font-mono text-zinc-400">Qty & Omzet</span>
            </div>
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 uppercase text-[10px]">
                        <tr>
                            <th class="py-2 px-3 w-8 text-center">#</th>
                            <th class="py-2 px-3">Produk</th>
                            <th class="py-2 px-3 text-right">Terjual</th>
                            <th class="py-2 px-3 text-right">Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                        @forelse($topProducts as $idx => $tp)
                        <tr>
                            <td class="py-2 px-3 text-center text-zinc-400">{{ $idx + 1 }}</td>
                            <td class="py-2 px-3 font-sans">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $tp->nama_produk }}</div>
                                <div class="text-[10px] font-mono text-zinc-400">{{ $tp->kode_produk }}</div>
                            </td>
                            <td class="py-2 px-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($tp->total_qty) }}</td>
                            <td class="py-2 px-3 text-right font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($tp->total_omzet, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-xs text-zinc-400 font-sans">Belum ada produk terjual pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Filter & Pilihan Periode (Compact) -->
    <div class="no-print p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-3">
        <!-- Preset Tombol Cepat Periode -->
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-[11px] font-semibold text-zinc-500 mr-1 uppercase tracking-wide">Periode:</span>
            @php
                $periods = [
                    'today' => 'Hari Ini',
                    'yesterday' => 'Kemarin',
                    'this_week' => 'Minggu Ini',
                    'this_month' => 'Bulan Ini',
                    'last_month' => 'Bulan Lalu',
                ];
            @endphp
            @foreach($periods as $key => $label)
                <button
                    type="button"
                    wire:click="setPeriod('{{ $key }}')"
                    class="px-2.5 py-1 rounded text-xs font-mono {{ $period === $key ? 'bg-zinc-800 text-white dark:bg-zinc-100 dark:text-zinc-900 border-zinc-900 dark:border-zinc-100 font-bold' : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200' }} border transition"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-800 items-end">
            <!-- Dari Tanggal -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Dari Tanggal</label>
                <input
                    type="date"
                    wire:model.live="startDate"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Sampai Tanggal -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Sampai Tanggal</label>
                <input
                    type="date"
                    wire:model.live="endDate"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Filter Kasir -->
            <div class="space-y-1">
                <label class="block text-[11px] font-medium text-zinc-500">Filter Kasir</label>
                <select
                    wire:model.live="selectedKasir"
                    class="w-full text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Semua Kasir --</option>
                    @foreach($kasirList as $k)
                        <option value="{{ $k->id }}">{{ $k->name }} ({{ $k->role->label() }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Penjualan (High-Density) -->
    <div class="no-print rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 uppercase text-[11px] font-semibold border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-2.5 px-3">No. Transaksi</th>
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3">Kasir</th>
                        <th class="py-2.5 px-3 text-center">Item</th>
                        <th class="py-2.5 px-3 text-right">Total Belanja</th>
                        <th class="py-2.5 px-3 text-right">Bayar</th>
                        <th class="py-2.5 px-3 text-right">Kembalian</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                    @forelse($sales as $s)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                            <td class="py-2 px-3 font-bold text-zinc-900 dark:text-zinc-100">
                                <div>{{ $s->no_transaksi }}</div>
                                @if($s->is_dropship)
                                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-sans font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                    Dropship: {{ $s->dropshipper?->nama ?? '-' }}
                                </span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-[11px] text-zinc-500">
                                {{ $s->waktu->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2 px-3 font-sans font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $s->nama_kasir_struk }}
                                @if($s->cashShift?->nama_petugas && $s->kasir?->name && $s->cashShift->nama_petugas !== $s->kasir->name)
                                    <span class="text-[10px] font-mono text-zinc-400">({{ $s->kasir->name }})</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-center">
                                <span class="font-mono text-zinc-600 dark:text-zinc-400">{{ $s->items->sum('qty') }} item</span>
                            </td>
                            <td class="py-2 px-3 text-right font-bold text-zinc-900 dark:text-zinc-100">
                                <div>Rp {{ number_format($s->total, 0, ',', '.') }}</div>
                                <div class="text-[10px] font-normal {{ $s->status_pembayaran === 'piutang' ? 'text-amber-600 font-bold' : 'text-zinc-400' }}">
                                    {{ strtoupper($s->metode_pembayaran ?? 'cash') }}{{ $s->status_pembayaran === 'piutang' ? ' (BON)' : '' }}
                                </div>
                            </td>
                            <td class="py-2 px-3 text-right text-zinc-500">
                                Rp {{ number_format($s->bayar, 0, ',', '.') }}
                            </td>
                            <td class="py-2 px-3 text-right text-zinc-600 dark:text-zinc-300">
                                Rp {{ number_format($s->kembali, 0, ',', '.') }}
                            </td>
                            <td class="py-2 px-3 text-center font-sans">
                                <button
                                    type="button"
                                    wire:click="showReceipt('{{ $s->id }}')"
                                    class="px-2 py-1 rounded text-xs font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 transition"
                                >
                                    Struk / Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-xs text-zinc-400 font-sans">
                                Tidak ada transaksi penjualan yang tercatat pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 font-sans">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL CETAK STRUK PEMBAYARAN KASIR -->
    <flux:modal wire:model="showReceiptModal" class="md:w-[440px] space-y-4">
        <div class="no-print">
            <div class="flex items-center gap-2">
                <flux:icon name="printer" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <flux:heading size="lg">Detail & Cetak Ulang Struk</flux:heading>
            </div>
            <flux:subheading>Pratinjau struk kasir untuk transaksi yang dipilih.</flux:subheading>
        </div>

        @if($selectedSale)
        @php $storeProfile = \App\Models\StoreProfile::getProfile(); @endphp
        <!-- Area Struk Thermal 80mm/58mm -->
        <div id="laporan-receipt-print-area" class="p-4 bg-white text-zinc-900 border border-dashed border-zinc-300 rounded shadow-inner font-mono text-xs leading-relaxed space-y-2">
            <!-- Header Toko -->
            <div class="text-center space-y-0.5 pb-2 border-b border-dashed border-zinc-400">
                <div class="text-base font-bold tracking-wider">{{ $storeProfile->nama_toko }}</div>
                @if($storeProfile->slogan)
                <div class="text-[10px] text-zinc-600">{{ $storeProfile->slogan }}</div>
                @endif
                <div class="text-[10px] text-zinc-500">{{ $storeProfile->alamat }} {{ filled($storeProfile->telepon) ? '- Telp: '.$storeProfile->telepon : '' }}</div>
            </div>

            <!-- Info Transaksi -->
            <div class="text-[11px] space-y-0.5 pb-1 border-b border-dashed border-zinc-300">
                <div class="flex justify-between">
                    <span>No. Transaksi:</span>
                    <span class="font-bold">{{ $selectedSale->no_transaksi }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Waktu:</span>
                    <span>{{ $selectedSale->waktu->format('d/m/Y H:i:s') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Kasir:</span>
                    <span>{{ $selectedSale->nama_kasir_struk }}</span>
                </div>
                @if($selectedSale->customer)
                <div class="flex justify-between">
                    <span>Member:</span>
                    <span>{{ $selectedSale->customer->nama }}</span>
                </div>
                @endif
                @if($selectedSale->is_dropship)
                <div class="pt-1 mt-1 border-t border-dashed border-zinc-300 space-y-0.5">
                    <div class="flex justify-between font-bold text-indigo-700">
                        <span>Dropshipper:</span>
                        <span>{{ $selectedSale->dropshipper?->nama ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Penerima:</span>
                        <span>{{ $selectedSale->dropship_penerima_nama }}</span>
                    </div>
                </div>
                @endif
            </div>

            <!-- Daftar Item Belanja -->
            <div class="space-y-1.5 py-1 border-b border-dashed border-zinc-300">
                @foreach($selectedSale->items as $item)
                <div>
                    <div class="font-semibold">{{ $item->nama_produk }}</div>
                    <div class="flex justify-between text-[11px] text-zinc-600">
                        <span>{{ $item->qty }} x {{ number_format($item->harga_satuan, 0, ',', '.') }}</span>
                        <span class="font-bold text-zinc-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Rincian Total, Bayar & Kembali -->
            <div class="space-y-1 pt-1 pb-2 border-b border-dashed border-zinc-400 text-xs">
                <div class="flex justify-between text-[11px] text-zinc-600">
                    <span>Total Item / Qty:</span>
                    <span>{{ $selectedSale->items->count() }} jenis ({{ $selectedSale->items->sum('qty') }} pcs)</span>
                </div>
                <div class="flex justify-between font-bold text-sm">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($selectedSale->total, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Tunai / Bayar</span>
                    <span>Rp {{ number_format($selectedSale->bayar, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between font-bold">
                    <span>Kembali</span>
                    <span>Rp {{ number_format($selectedSale->kembali, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Footer Struk -->
            <div class="text-center text-[10px] text-zinc-500 pt-2 space-y-0.5">
                <div>*** TERIMA KASIH ATAS KUNJUNGAN ANDA ***</div>
                <div>{{ $storeProfile->footer_struk ?: 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan' }}</div>
            </div>
        </div>

        @if($selectedSale->is_dropship)
        <div id="laporan-dropship-label-print-area" class="hidden p-4 bg-white text-zinc-900 border-2 border-zinc-800 rounded font-mono text-xs leading-relaxed space-y-2">
            <div class="text-center pb-2 border-b-2 border-zinc-800">
                <div class="text-sm font-black uppercase tracking-wider">LABEL PENGIRIMAN DROPSHIP</div>
                <div class="text-[11px] font-bold">Ref Order: {{ $selectedSale->no_transaksi }}</div>
            </div>
            <div class="pb-2 border-b border-dashed border-zinc-400 space-y-0.5">
                <div class="text-[10px] uppercase font-bold text-zinc-500">PENGIRIM (DROPSHIPPER):</div>
                <div class="font-bold text-sm">{{ $selectedSale->dropshipper?->nama ?? 'Dropshipper' }}</div>
                @if($selectedSale->dropshipper?->telepon)
                <div class="text-[11px]">Telp: {{ $selectedSale->dropshipper->telepon }}</div>
                @endif
            </div>
            <div class="pb-2 border-b border-dashed border-zinc-400 space-y-0.5">
                <div class="text-[10px] uppercase font-bold text-zinc-500">KEPADA (PENERIMA):</div>
                <div class="font-bold text-sm">{{ $selectedSale->dropship_penerima_nama }}</div>
                @if($selectedSale->dropship_penerima_telepon)
                <div class="text-[11px]">Telp: {{ $selectedSale->dropship_penerima_telepon }}</div>
                @endif
                @if($selectedSale->dropship_penerima_alamat)
                <div class="text-[11px] whitespace-normal">{{ $selectedSale->dropship_penerima_alamat }}</div>
                @endif
            </div>
            <div class="space-y-1">
                <div class="text-[10px] uppercase font-bold text-zinc-500">ISI PAKET ({{ $selectedSale->items->sum('qty') }} PCS):</div>
                @foreach($selectedSale->items as $item)
                <div class="flex justify-between text-[11px]">
                    <span>{{ $item->nama_produk }}</span>
                    <span class="font-bold">x{{ $item->qty }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endif

        <!-- Tombol Aksi Modal Struk -->
        @php $defaultPaper = ($storeProfile ?? \App\Models\StoreProfile::getProfile())->ukuran_kertas ?? '80mm'; @endphp
        <div class="no-print space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                <span class="font-medium">Ukuran Kertas:</span>
                <select id="laporan-paper-size" class="text-xs bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded px-2.5 py-1 font-medium text-zinc-800 dark:text-zinc-200 focus:outline-none">
                    <option value="80mm" @selected($defaultPaper === '80mm')>Thermal 80mm</option>
                    <option value="58mm" @selected($defaultPaper === '58mm')>Thermal 58mm</option>
                </select>
            </div>
            <div class="flex items-center justify-between gap-2">
                <flux:button type="button" wire:click="closeReceiptModal" variant="subtle" size="sm">
                    Tutup
                </flux:button>
                <div class="flex items-center gap-2">
                    @if($selectedSale?->is_dropship)
                    <flux:button
                        type="button"
                        onclick="const s = document.getElementById('laporan-paper-size')?.value || '{{ $defaultPaper }}'; window.printThermalReceipt('laporan-dropship-label-print-area', s);"
                        variant="subtle"
                        size="sm"
                        icon="truck"
                    >
                        Cetak Label Dropship
                    </flux:button>
                    @endif
                    <flux:button
                        type="button"
                        onclick="const s = document.getElementById('laporan-paper-size')?.value || '{{ $defaultPaper }}'; window.printThermalReceipt('laporan-receipt-print-area', s);"
                        variant="primary"
                        size="sm"
                        class="bg-emerald-600 hover:bg-emerald-700"
                        icon="printer"
                    >
                        Cetak Struk
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
