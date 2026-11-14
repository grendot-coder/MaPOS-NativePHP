<div class="space-y-6">
    <!-- CSS Cetak Struk Khusus (@media print fallback) -->
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #laporan-receipt-print-area, #laporan-receipt-print-area * {
                visibility: visible !important;
            }
            #laporan-receipt-print-area {
                position: absolute !important;
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
                size: auto;
                margin: 0;
            }
        }
    </style>

    <!-- Header -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Laporan Penjualan</flux:heading>
                <flux:badge color="emerald" size="sm">Omzet & Rekap Nota</flux:badge>
            </div>
            <flux:subheading>Rekapitulasi penjualan kasir, total pendapatan, rincian nota, dan cetak ulang struk belanja.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('kasir.pos') }}" variant="subtle" icon="shopping-cart" wire:navigate>
                Buka Kasir POS
            </flux:button>
        </div>
    </div>

    <!-- KPI Ringkasan Statistik -->
    <div class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Omzet -->
        <flux:card class="p-4 bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center gap-3">
            <div class="size-11 flex items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                <flux:icon name="banknotes" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-emerald-800 dark:text-emerald-300 font-medium">Total Omzet Penjualan</div>
                <div class="text-xl font-black font-mono text-emerald-700 dark:text-emerald-300">
                    Rp {{ number_format($totalOmzet, 0, ',', '.') }}
                </div>
            </div>
        </flux:card>

        <!-- Total Transaksi -->
        <flux:card class="p-4 flex items-center gap-3">
            <div class="size-11 flex items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                <flux:icon name="receipt-percent" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-zinc-500">Total Transaksi (Nota)</div>
                <div class="text-xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                    {{ number_format($totalTransaksi) }} Transaksi
                </div>
            </div>
        </flux:card>

        <!-- Total Item Terjual -->
        <flux:card class="p-4 flex items-center gap-3">
            <div class="size-11 flex items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                <flux:icon name="shopping-bag" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-zinc-500">Total Barang Terjual</div>
                <div class="text-xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                    {{ number_format($totalItemTerjual) }} Pcs / Item
                </div>
            </div>
        </flux:card>

        <!-- Rata-rata Belanja -->
        <flux:card class="p-4 flex items-center gap-3">
            <div class="size-11 flex items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                <flux:icon name="calculator" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-zinc-500">Rata-Rata per Transaksi</div>
                <div class="text-xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                    Rp {{ number_format($rataRataBelanja, 0, ',', '.') }}
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filter & Pilihan Periode -->
    <flux:card class="no-print p-4 space-y-3">
        <!-- Preset Tombol Cepat Periode -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-zinc-500">Periode:</span>
            <button
                type="button"
                wire:click="setPeriod('today')"
                class="px-3 py-1 rounded-md text-xs font-semibold border {{ $period === 'today' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200' }} transition"
            >
                Hari Ini
            </button>
            <button
                type="button"
                wire:click="setPeriod('yesterday')"
                class="px-3 py-1 rounded-md text-xs font-semibold border {{ $period === 'yesterday' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200' }} transition"
            >
                Kemarin
            </button>
            <button
                type="button"
                wire:click="setPeriod('this_week')"
                class="px-3 py-1 rounded-md text-xs font-semibold border {{ $period === 'this_week' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200' }} transition"
            >
                Minggu Ini
            </button>
            <button
                type="button"
                wire:click="setPeriod('this_month')"
                class="px-3 py-1 rounded-md text-xs font-semibold border {{ $period === 'this_month' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200' }} transition"
            >
                Bulan Ini
            </button>
            <button
                type="button"
                wire:click="setPeriod('last_month')"
                class="px-3 py-1 rounded-md text-xs font-semibold border {{ $period === 'last_month' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200' }} transition"
            >
                Bulan Lalu
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 border-t border-zinc-200 dark:border-zinc-800 items-end">
            <!-- Dari Tanggal -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Dari Tanggal</label>
                <input
                    type="date"
                    wire:model.live="startDate"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Sampai Tanggal -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Sampai Tanggal</label>
                <input
                    type="date"
                    wire:model.live="endDate"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                />
            </div>

            <!-- Filter Kasir -->
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-500">Filter Kasir</label>
                <select
                    wire:model.live="selectedKasir"
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                >
                    <option value="">-- Semua Kasir --</option>
                    @foreach($kasirList as $k)
                        <option value="{{ $k->id }}">{{ $k->name }} ({{ $k->role->label() }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </flux:card>

    <!-- Tabel Daftar Penjualan -->
    <flux:card class="no-print p-0 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-semibold uppercase">
                    <tr>
                        <th class="py-3 px-4">No. Transaksi</th>
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Kasir</th>
                        <th class="py-3 px-4 text-center">Item</th>
                        <th class="py-3 px-4 text-right">Total Belanja</th>
                        <th class="py-3 px-4 text-right">Bayar</th>
                        <th class="py-3 px-4 text-right">Kembalian</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($sales as $s)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                            <td class="py-3 px-4 font-mono font-bold text-xs text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                {{ $s->no_transaksi }}
                            </td>
                            <td class="py-3 px-4 text-xs font-mono text-zinc-500 whitespace-nowrap">
                                {{ $s->waktu->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                {{ $s->kasir->name ?? 'Kasir Dihapus' }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono">
                                <flux:badge size="sm" color="zinc">{{ $s->items->sum('qty') }} item</flux:badge>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                Rp {{ number_format($s->total, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-xs text-zinc-500 whitespace-nowrap">
                                Rp {{ number_format($s->bayar, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-xs text-emerald-600 dark:text-emerald-400 font-semibold whitespace-nowrap">
                                Rp {{ number_format($s->kembali, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <flux:button
                                    type="button"
                                    wire:click="showReceipt('{{ $s->id }}')"
                                    size="sm"
                                    variant="subtle"
                                    icon="document-magnifying-glass"
                                >
                                    Struk / Detail
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-zinc-400">
                                <div class="size-10 mx-auto flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-400 mb-2">
                                    <flux:icon name="receipt-percent" class="size-5" />
                                </div>
                                Tidak ada transaksi penjualan yang tercatat pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
                {{ $sales->links() }}
            </div>
        @endif
    </flux:card>

    <!-- Modal Detail & Cetak Ulang Struk -->
    <flux:modal wire:model="showReceiptModal" class="md:w-[440px] space-y-4">
        <div class="no-print">
            <div class="flex items-center gap-2">
                <flux:icon name="printer" class="size-6 text-emerald-600 dark:text-emerald-400" />
                <flux:heading size="lg">Salinan Struk Penjualan</flux:heading>
            </div>
            <flux:subheading>Detail nota belanja pelanggan & cetak ulang (*reprint*).</flux:subheading>
        </div>

        @if($selectedSale)
            <!-- Area Preview / Cetak Struk (Thermal 58mm/80mm) -->
            <div id="laporan-receipt-print-area" class="p-4 bg-white text-zinc-900 border border-dashed border-zinc-300 rounded-lg shadow-inner font-mono text-xs leading-relaxed space-y-2">
                <!-- Header Toko -->
                <div class="text-center space-y-0.5 pb-2 border-b border-dashed border-zinc-400">
                    <div class="text-base font-bold tracking-wider">MAPOS MINIMARKET</div>
                    <div class="text-[10px] text-zinc-600">Sistem Kasir & Manajemen POS</div>
                    <div class="text-[10px] text-zinc-500">Jl. Pelajar PKL No. 10 - Telp: (021) 555-MAPOS</div>
                    <div class="text-[9px] uppercase tracking-widest text-zinc-400 font-bold mt-1">*** SALINAN / REPRINT ***</div>
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
                        <span>{{ $selectedSale->kasir->name ?? 'Kasir' }}</span>
                    </div>
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
                            @php
                                $normalSubtotal = $item->qty * $item->harga_satuan;
                                $hematItem = $normalSubtotal - $item->subtotal;
                            @endphp
                            @if($hematItem > 0)
                            <div class="flex justify-between text-[10px] text-emerald-700 italic">
                                <span>* Diskon item</span>
                                <span>-Rp {{ number_format($hematItem, 0, ',', '.') }}</span>
                            </div>
                            @endif
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
                    <div>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</div>
                </div>
            </div>
        @endif

        <div class="no-print space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                <span class="font-medium">Ukuran Kertas Thermal:</span>
                <select id="laporan-paper-size" class="text-xs bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded px-2.5 py-1 font-medium text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="80mm" selected>Thermal 80mm (Standar POS)</option>
                    <option value="58mm">Thermal 58mm (Mini POS)</option>
                </select>
            </div>
            <div class="flex items-center justify-between gap-2">
                <flux:button type="button" wire:click="closeReceiptModal" variant="subtle">
                    Tutup
                </flux:button>
                <flux:button
                    type="button"
                    onclick="const s = document.getElementById('laporan-paper-size')?.value || '80mm'; window.printThermalReceipt('laporan-receipt-print-area', s);"
                    variant="primary"
                    class="bg-emerald-600 hover:bg-emerald-700"
                    icon="printer">
                    Cetak Ulang Struk
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
