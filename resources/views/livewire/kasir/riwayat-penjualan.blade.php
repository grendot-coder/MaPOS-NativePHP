<div class="space-y-6">
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
            #kasir-receipt-print-area, #kasir-receipt-print-area * {
                visibility: visible !important;
            }
            #kasir-receipt-print-area {
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

    <!-- Header Kasir -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Riwayat Transaksi Saya</flux:heading>
                <flux:badge color="emerald" size="sm">Kasir: {{ auth()->user()->name }}</flux:badge>
            </div>
            <flux:subheading>Daftar nota transaksi penjualan pada shift Anda dan cetak ulang struk pelanggan.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('kasir.pos') }}" variant="primary" icon="shopping-cart" class="bg-emerald-600 hover:bg-emerald-700" wire:navigate>
                Kembali ke Layar Kasir POS
            </flux:button>
        </div>
    </div>

    <!-- Ringkasan Shift Kasir -->
    <div class="no-print grid grid-cols-1 sm:grid-cols-2 gap-4">
        <flux:card class="p-4 bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center gap-3">
            <div class="size-11 flex items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                <flux:icon name="banknotes" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-emerald-800 dark:text-emerald-300 font-medium">Total Uang Penjualan (Shift)</div>
                <div class="text-2xl font-black font-mono text-emerald-700 dark:text-emerald-300">
                    Rp {{ number_format($totalOmzet, 0, ',', '.') }}
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 flex items-center gap-3">
            <div class="size-11 flex items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                <flux:icon name="receipt-percent" class="size-6" />
            </div>
            <div>
                <div class="text-xs text-zinc-500 font-medium">Total Pelanggan Dilayani</div>
                <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                    {{ number_format($totalTransaksi) }} Nota
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filter Tanggal -->
    <flux:card class="no-print p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Pilih Tanggal Transaksi:</label>
            <input
                type="date"
                wire:model.live="filterDate"
                class="text-xs font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-900 dark:text-zinc-100"
            />
        </div>
        <button
            type="button"
            wire:click="$set('filterDate', '{{ now()->format('Y-m-d') }}')"
            class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline"
        >
            Kembali ke Hari Ini
        </button>
    </flux:card>

    <!-- Tabel Daftar Penjualan Kasir -->
    <flux:card class="no-print p-0 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-semibold uppercase">
                    <tr>
                        <th class="py-3 px-4">No. Transaksi</th>
                        <th class="py-3 px-4">Jam</th>
                        <th class="py-3 px-4 text-center">Item</th>
                        <th class="py-3 px-4 text-right">Total Tagihan</th>
                        <th class="py-3 px-4 text-right">Uang Diterima</th>
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
                                {{ $s->waktu->format('H:i:s') }}
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
                                    icon="printer"
                                >
                                    Cetak Ulang
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-zinc-400">
                                <div class="size-10 mx-auto flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-400 mb-2">
                                    <flux:icon name="receipt-percent" class="size-5" />
                                </div>
                                Belum ada transaksi penjualan pada tanggal ini.
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

    <!-- Modal Cetak Ulang Struk Kasir -->
    <flux:modal wire:model="showReceiptModal" class="md:w-[440px] space-y-4">
        <div class="no-print">
            <div class="flex items-center gap-2">
                <flux:icon name="printer" class="size-6 text-emerald-600 dark:text-emerald-400" />
                <flux:heading size="lg">Cetak Ulang Struk</flux:heading>
            </div>
            <flux:subheading>Salinan struk belanja kasir untuk pelanggan.</flux:subheading>
        </div>

        @if($selectedSale)
            <!-- Area Preview / Cetak Struk (Thermal 58mm/80mm) -->
            <div id="kasir-receipt-print-area" class="p-4 bg-white text-zinc-900 border border-dashed border-zinc-300 rounded-lg shadow-inner font-mono text-xs leading-relaxed space-y-2">
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
                <select id="kasir-paper-size" class="text-xs bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded px-2.5 py-1 font-medium text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
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
                    onclick="const s = document.getElementById('kasir-paper-size')?.value || '80mm'; window.printThermalReceipt('kasir-receipt-print-area', s);"
                    variant="primary"
                    class="bg-emerald-600 hover:bg-emerald-700"
                    icon="printer">
                    Cetak Ulang Struk
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
