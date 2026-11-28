<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Retur Barang (Penjualan & Pembelian)</flux:heading>
            <flux:subheading>Proses pengembalian barang dari pelanggan maupun retur barang rusak ke supplier dengan pembaruan stok otomatis.</flux:subheading>
        </div>
    </div>

    <!-- Tab Switcher -->
    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2">
        <button
            type="button"
            wire:click="switchTab('penjualan')"
            class="px-4 py-2 rounded text-xs font-bold transition {{ $activeTab === 'penjualan' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200' }}">
            Retur Penjualan (Pelanggan)
        </button>
        <button
            type="button"
            wire:click="switchTab('pembelian')"
            class="px-4 py-2 rounded text-xs font-bold transition {{ $activeTab === 'pembelian' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200' }}">
            Retur Pembelian (Ke Supplier)
        </button>
    </div>

    @if($activeTab === 'penjualan')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        <!-- Kiri: Pilih Nota Penjualan -->
        <flux:card class="lg:col-span-1 space-y-3">
            <flux:heading size="md">1. Pilih Nota Penjualan</flux:heading>
            <flux:input wire:model.live.debounce.300ms="saleSearch" placeholder="Cari No. TRX-..." icon="magnifying-glass" size="sm" clearable />

            <div class="divide-y divide-zinc-200 dark:divide-zinc-800 max-h-80 overflow-y-auto">
                @forelse($saleResults as $s)
                <button
                    type="button"
                    wire:click="selectSale('{{ $s->id }}')"
                    class="w-full text-left py-2.5 px-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition flex items-center justify-between text-xs {{ $selectedSaleId === $s->id ? 'bg-indigo-50/70 dark:bg-indigo-950/30 font-semibold' : '' }}">
                    <div>
                        <div class="font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $s->no_transaksi }}</div>
                        <div class="text-[11px] text-zinc-400">{{ $s->waktu->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="text-right font-mono">
                        <div class="font-bold">Rp {{ number_format($s->total, 0, ',', '.') }}</div>
                        <span class="text-[10px] uppercase {{ $s->status_pembayaran === 'piutang' ? 'text-amber-600' : 'text-emerald-600' }}">{{ $s->status_pembayaran }}</span>
                    </div>
                </button>
                @empty
                <div class="py-6 text-center text-xs text-zinc-400">Nota transaksi tidak ditemukan.</div>
                @endforelse
            </div>
        </flux:card>

        <!-- Kanan: Form Retur Item -->
        <flux:card class="lg:col-span-2 space-y-4">
            <flux:heading size="md">2. Rincian Barang Retur Penjualan</flux:heading>

            @error('saleReturn')
            <div class="p-3 rounded border border-rose-300 bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 text-xs">{{ $message }}</div>
            @enderror

            @if($selectedSale)
            <div class="p-3 rounded bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 text-xs flex flex-wrap justify-between gap-2 font-mono">
                <span>Nota: <strong>{{ $selectedSale->no_transaksi }}</strong></span>
                <span>Total Nota: <strong>Rp {{ number_format($selectedSale->total, 0, ',', '.') }}</strong></span>
                @if($selectedSale->customer)
                <span>Member: <strong>{{ $selectedSale->customer->nama }}</strong></span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 uppercase text-[11px]">
                        <tr>
                            <th class="p-2">Produk</th>
                            <th class="p-2 text-center">Beli</th>
                            <th class="p-2 text-center">Sudah Retur</th>
                            <th class="p-2 text-center">Sisa Bisa Retur</th>
                            <th class="p-2 text-center w-28">Qty Retur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                        @foreach($selectedSale->items as $item)
                        @php
                            $returned = (int) ($saleReturnedMap[$item->id] ?? 0);
                            $maxRet = max(0, $item->qty - $returned);
                        @endphp
                        <tr>
                            <td class="p-2 font-sans">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $item->nama_produk }}</div>
                                <div class="text-[11px] font-mono text-zinc-400">Rp {{ number_format($item->qty > 0 ? ($item->subtotal / $item->qty) : $item->harga_satuan, 0, ',', '.') }}/unit</div>
                            </td>
                            <td class="p-2 text-center">{{ $item->qty }}</td>
                            <td class="p-2 text-center text-zinc-400">{{ $returned }}</td>
                            <td class="p-2 text-center font-bold">{{ $maxRet }}</td>
                            <td class="p-2 text-center">
                                <input
                                    type="number"
                                    min="0"
                                    max="{{ $maxRet }}"
                                    wire:model="saleReturnQtys.{{ $item->id }}"
                                    @disabled($maxRet <= 0)
                                    class="w-20 text-center rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-1 text-xs font-bold" />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Metode Pengembalian Dana</label>
                    <select wire:model="saleRefundMethod" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                        <option value="cash">Refund Tunai Kasir (Potong Kas Shift)</option>
                        @if($selectedSale->status_pembayaran === 'piutang' && $selectedSale->customer_id)
                        <option value="potong_piutang">Potong Sisa Bon / Piutang Member</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Alasan Retur</label>
                    <flux:input wire:model="saleReturnReason" size="sm" placeholder="Contoh: Kemasan rusak / salah beli" />
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="button" wire:click="submitSaleReturn" variant="primary" size="sm" icon="arrow-uturn-left">
                    Proses Retur Penjualan
                </flux:button>
            </div>
            @else
            <div class="py-10 text-center text-xs text-zinc-400">
                Pilih nota penjualan di sebelah kiri untuk memulai proses retur.
            </div>
            @endif
        </flux:card>
    </div>

    <!-- Riwayat Retur Penjualan -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">
            Riwayat Retur Penjualan Terakhir
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 uppercase text-[11px]">
                    <tr>
                        <th class="p-3">No. Retur</th>
                        <th class="p-3">Nota Asal</th>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Metode Refund</th>
                        <th class="p-3 text-right">Total Refund</th>
                        <th class="p-3">Alasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                    @forelse($recentSaleReturns as $r)
                    <tr>
                        <td class="p-3 font-bold">{{ $r->no_retur }}</td>
                        <td class="p-3 text-zinc-600 dark:text-zinc-400">{{ $r->sale->no_transaksi ?? '-' }}</td>
                        <td class="p-3 text-zinc-500">{{ $r->waktu->format('d/m/Y H:i') }}</td>
                        <td class="p-3 font-sans uppercase text-[11px]">{{ str_replace('_', ' ', $r->metode_refund) }}</td>
                        <td class="p-3 text-right font-bold text-rose-600">Rp {{ number_format($r->total_refund, 0, ',', '.') }}</td>
                        <td class="p-3 font-sans text-zinc-500">{{ $r->alasan ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-zinc-400 font-sans">Belum ada riwayat retur penjualan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <!-- TAB RETUR PEMBELIAN -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        <flux:card class="lg:col-span-1 space-y-3">
            <flux:heading size="md">1. Pilih Faktur Pembelian</flux:heading>
            <flux:input wire:model.live.debounce.300ms="purchaseSearch" placeholder="Cari No. PB-... atau supplier" icon="magnifying-glass" size="sm" clearable />

            <div class="divide-y divide-zinc-200 dark:divide-zinc-800 max-h-80 overflow-y-auto">
                @forelse($purchaseResults as $p)
                <button
                    type="button"
                    wire:click="selectPurchase('{{ $p->id }}')"
                    class="w-full text-left py-2.5 px-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition flex items-center justify-between text-xs {{ $selectedPurchaseId === $p->id ? 'bg-indigo-50/70 dark:bg-indigo-950/30 font-semibold' : '' }}">
                    <div>
                        <div class="font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $p->no_faktur }}</div>
                        <div class="text-[11px] text-zinc-400">{{ $p->nama_supplier }}</div>
                    </div>
                    <div class="text-right font-mono">
                        <div class="font-bold">Rp {{ number_format($p->total, 0, ',', '.') }}</div>
                        <span class="text-[10px] uppercase {{ $p->status_pembayaran === 'hutang' ? 'text-amber-600' : 'text-emerald-600' }}">{{ $p->status_pembayaran }}</span>
                    </div>
                </button>
                @empty
                <div class="py-6 text-center text-xs text-zinc-400">Faktur pembelian tidak ditemukan.</div>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="lg:col-span-2 space-y-4">
            <flux:heading size="md">2. Rincian Barang Retur ke Supplier</flux:heading>

            @error('purchaseReturn')
            <div class="p-3 rounded border border-rose-300 bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 text-xs">{{ $message }}</div>
            @enderror

            @if($selectedPurchase)
            <div class="p-3 rounded bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 text-xs flex flex-wrap justify-between gap-2 font-mono">
                <span>Faktur: <strong>{{ $selectedPurchase->no_faktur }}</strong></span>
                <span>Supplier: <strong>{{ $selectedPurchase->nama_supplier }}</strong></span>
                <span>Status: <strong class="uppercase">{{ $selectedPurchase->status_pembayaran }}</strong></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 uppercase text-[11px]">
                        <tr>
                            <th class="p-2">Produk</th>
                            <th class="p-2 text-center">Qty Beli</th>
                            <th class="p-2 text-center">Sudah Retur</th>
                            <th class="p-2 text-center">Sisa Bisa Retur</th>
                            <th class="p-2 text-center w-28">Qty Retur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                        @foreach($selectedPurchase->items as $item)
                        @php
                            $returned = (int) ($purchaseReturnedMap[$item->id] ?? 0);
                            $maxRet = max(0, $item->qty - $returned);
                        @endphp
                        <tr>
                            <td class="p-2 font-sans">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $item->nama_produk }}</div>
                                <div class="text-[11px] font-mono text-zinc-400">Modal: Rp {{ number_format($item->harga_beli, 0, ',', '.') }} &bull; Stok skrg: {{ $item->product->stok ?? 0 }}</div>
                            </td>
                            <td class="p-2 text-center">{{ $item->qty }}</td>
                            <td class="p-2 text-center text-zinc-400">{{ $returned }}</td>
                            <td class="p-2 text-center font-bold">{{ $maxRet }}</td>
                            <td class="p-2 text-center">
                                <input
                                    type="number"
                                    min="0"
                                    max="{{ $maxRet }}"
                                    wire:model="purchaseReturnQtys.{{ $item->id }}"
                                    @disabled($maxRet <= 0)
                                    class="w-20 text-center rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-1 text-xs font-bold" />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Penyelesaian Supplier</label>
                    <select wire:model="purchaseRefundMethod" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                        @if($selectedPurchase->status_pembayaran === 'hutang')
                        <option value="potong_hutang">Potong Sisa Hutang Faktur</option>
                        @endif
                        <option value="refund_tunai">Pengembalian Dana Supplier / Tukar Barang</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Alasan Retur</label>
                    <flux:input wire:model="purchaseReturnReason" size="sm" placeholder="Contoh: Barang kadaluwarsa / cacat pabrik" />
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="button" wire:click="submitPurchaseReturn" variant="primary" size="sm" icon="arrow-uturn-left" class="bg-amber-600 hover:bg-amber-700">
                    Proses Retur Pembelian
                </flux:button>
            </div>
            @else
            <div class="py-10 text-center text-xs text-zinc-400">
                Pilih faktur pembelian di sebelah kiri untuk memulai proses retur ke supplier.
            </div>
            @endif
        </flux:card>
    </div>

    <!-- Riwayat Retur Pembelian -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">
            Riwayat Retur Pembelian Terakhir
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 uppercase text-[11px]">
                    <tr>
                        <th class="p-3">No. Retur</th>
                        <th class="p-3">Faktur Asal</th>
                        <th class="p-3">Waktu</th>
                        <th class="p-3">Penyelesaian</th>
                        <th class="p-3 text-right">Nilai Retur</th>
                        <th class="p-3">Alasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                    @forelse($recentPurchaseReturns as $r)
                    <tr>
                        <td class="p-3 font-bold">{{ $r->no_retur }}</td>
                        <td class="p-3 text-zinc-600 dark:text-zinc-400">{{ $r->purchase->no_faktur ?? '-' }}</td>
                        <td class="p-3 text-zinc-500">{{ $r->waktu->format('d/m/Y H:i') }}</td>
                        <td class="p-3 font-sans uppercase text-[11px]">{{ str_replace('_', ' ', $r->metode_pengembalian) }}</td>
                        <td class="p-3 text-right font-bold text-amber-600">Rp {{ number_format($r->total_retur, 0, ',', '.') }}</td>
                        <td class="p-3 font-sans text-zinc-500">{{ $r->alasan ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-zinc-400 font-sans">Belum ada riwayat retur pembelian.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
