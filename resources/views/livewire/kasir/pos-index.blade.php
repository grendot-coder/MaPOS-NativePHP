<div
    x-data="{
        showGuide: false,
        focusScanner() {
            this.$nextTick(() => {
                const modal = document.querySelector('flux-modal[open], [data-flux-modal][open]');
                if (modal) return;
                const el = document.getElementById('barcode-input-field');
                if (el) el.focus();
            });
        }
    }"
    x-init="
        focusScanner();
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', () => focusScanner());
        }
    "
    @keydown.window.f2.prevent="$wire.openSearchModal()"
    @keydown.window.f8.prevent="$wire.openPayment()"
    @keydown.window.f9.prevent="if(confirm('Kosongkan keranjang belanja?')) { $wire.clearCart(); }"
    @keydown.window.f10.prevent="$wire.confirmCloseShift()"
    class="space-y-4">
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

            #receipt-print-area,
            #receipt-print-area *,
            #shift-receipt-print-area,
            #shift-receipt-print-area * {
                visibility: visible !important;
            }

            #receipt-print-area,
            #shift-receipt-print-area {
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

    <!-- Header Kasir: Status Bar Bersih & Profesional -->
    <div class="no-print flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl" level="1">Point of Sale (Kasir)</flux:heading>
                <span class="text-xs font-mono px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                    TRX: <strong class="text-zinc-900 dark:text-zinc-100">{{ $nextTransactionNumber }}</strong>
                </span>
            </div>
            <flux:subheading class="mt-0.5">
                Kasir: <strong>{{ auth()->user()->name }}</strong> &bull;
                {{ now()->translatedFormat('l, d F Y') }}
            </flux:subheading>
        </div>

        <!-- Tombol Aksi Cepat Header, Status Shift Laci & Bantuan HP Scanner -->
        <div class="flex flex-wrap items-center gap-2">
            @if($activeShift)
            <div class="flex items-center gap-2.5 px-3 py-1.5 rounded bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 text-xs font-mono">
                <div class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                    <span class="inline-block size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Modal: <strong class="text-zinc-900 dark:text-zinc-100">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</strong></span>
                </div>
                <div class="h-3 w-px bg-zinc-300 dark:bg-zinc-700"></div>
                <div class="text-zinc-600 dark:text-zinc-400">
                    <span>Kas Laci: <strong class="text-emerald-600 dark:text-emerald-400 font-bold">Rp {{ number_format($saldoKasSaatIni, 0, ',', '.') }}</strong></span>
                </div>
            </div>

            <button
                type="button"
                wire:click="confirmCloseShift"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/50 border border-rose-200 dark:border-rose-800 transition"
                title="Tutup Shift & Rekap Laci Kasir [F10]">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5" />
                <span>Tutup Shift <kbd class="font-mono text-[10px] font-bold">[F10]</kbd></span>
            </button>
            @else
            <button
                type="button"
                wire:click="$set('showOpenShiftModal', true)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-semibold text-white bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition">
                <flux:icon name="lock-open" class="size-3.5" />
                <span>+ Buka Shift Kasir</span>
            </button>
            @endif

            <button
                type="button"
                @click="showGuide = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-zinc-100 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 transition"
                title="Panduan Koneksi Barcode to PC">
                <flux:icon name="device-phone-mobile" class="size-4 text-emerald-600 dark:text-emerald-400" />
                <span>HP Scanner</span>
            </button>

            <button
                type="button"
                wire:click="openSearchModal"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-medium text-zinc-700 dark:text-zinc-200 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 transition">
                <flux:icon name="magnifying-glass" class="size-3.5 text-zinc-500" />
                <span>Cari <kbd class="font-mono font-bold text-zinc-500">[F2]</kbd></span>
            </button>
        </div>
    </div>

    <!-- Layout Workstation POS: 68% Area Kerja (Scanner, Keranjang, Produk Cepat) | 32% Panel Checkout Kasir -->
    <div class="no-print grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- AREA KIRI (lg:col-span-8): Barcode Input & Keranjang Belanja Dominan -->
        <div class="lg:col-span-8 space-y-4">

            <!-- Bar Pemindai Barcode (Scanner Bar) -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <label for="barcode-input-field" class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 flex items-center gap-2">
                        <flux:icon name="qr-code" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span>Pemindai Barcode</span>
                    </label>
                    <span class="text-[11px] text-zinc-400 font-mono">Autofocus aktif &bull; Tekan Enter setelah scan</span>
                </div>

                <form wire:submit.prevent="scanBarcode" @submit="focusScanner()" class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input
                            id="barcode-input-field"
                            wire:model="barcodeInput"
                            type="text"
                            placeholder="Arahkan scanner ke barcode atau ketik kode produk..."
                            class="w-full py-2.5 pl-10 pr-4 text-sm font-mono rounded border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-500 focus:bg-white dark:focus:bg-zinc-900 transition"
                            autocomplete="off"
                            autofocus />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                            <flux:icon name="bars-3-bottom-left" class="size-4" />
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="openSearchModal"
                        class="px-3.5 py-2.5 rounded border border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-xs font-medium text-zinc-700 dark:text-zinc-300 transition whitespace-nowrap flex items-center gap-1.5">
                        <flux:icon name="magnifying-glass" class="size-4" />
                        <span>[F2] Cari</span>
                    </button>
                </form>
            </div>

            <!-- Tabel Keranjang Belanja (High-Density) -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm">
                <!-- Baris Judul Keranjang -->
                <div class="px-4 py-2.5 bg-zinc-100/70 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="shopping-cart" class="size-4 text-zinc-600 dark:text-zinc-400" />
                        <span class="font-bold text-xs uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Keranjang Belanja</span>
                        <span class="text-xs font-mono font-medium text-zinc-500">({{ count($cart) }} item)</span>
                    </div>

                    @if(count($cart) > 0)
                    <button
                        type="button"
                        wire:click="clearCart"
                        wire:confirm="Kosongkan seluruh item di keranjang?"
                        class="text-xs font-medium text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1">
                        <flux:icon name="trash" class="size-3.5" />
                        <span>Kosongkan [F9]</span>
                    </button>
                    @endif
                </div>

                <!-- Kontainer Tabel Scrollable -->
                <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/90 text-zinc-500 dark:text-zinc-400 uppercase text-[11px] font-semibold border-b border-zinc-200 dark:border-zinc-800 sticky top-0 z-10">
                            <tr>
                                <th class="py-2 px-3 w-8 text-center">#</th>
                                <th class="py-2 px-3">Produk & Barcode</th>
                                <th class="py-2 px-3 text-right">Harga</th>
                                <th class="py-2 px-3 text-center w-28">Qty</th>
                                <th class="py-2 px-3 text-right">Subtotal</th>
                                <th class="py-2 px-3 w-10 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                            @php $no = 1; @endphp
                            @forelse($cart as $id => $item)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                                <td class="py-2 px-3 text-center text-zinc-400 font-normal">
                                    {{ $no++ }}
                                </td>
                                <td class="py-2 px-3 font-sans">
                                    <div class="font-semibold text-zinc-900 dark:text-zinc-100 text-xs">
                                        {{ $item['nama'] }}
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] font-mono text-zinc-500">
                                        <span>{{ $item['kode_produk'] }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $item['satuan'] }}</span>
                                        @if($item['diskon'] > 0)
                                        <span class="text-rose-600 dark:text-rose-400 font-semibold font-sans">Disc {{ $item['diskon'] }}%</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-right whitespace-nowrap">
                                    @if($item['diskon'] > 0)
                                    <div class="text-[10px] line-through text-zinc-400">
                                        Rp {{ number_format($item['harga_satuan'], 0, ',', '.') }}
                                    </div>
                                    <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                        Rp {{ number_format($item['harga_satuan'] * (1 - $item['diskon'] / 100), 0, ',', '.') }}
                                    </div>
                                    @else
                                    <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                        Rp {{ number_format($item['harga_satuan'], 0, ',', '.') }}
                                    </div>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            wire:click="decrementQty('{{ $id }}')"
                                            class="size-6 flex items-center justify-center rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 font-bold transition">
                                            &minus;
                                        </button>
                                        <input
                                            type="number"
                                            min="1"
                                            max="{{ $item['stok_max'] }}"
                                            value="{{ $item['qty'] }}"
                                            wire:change="updateQty('{{ $id }}', $event.target.value)"
                                            class="w-12 text-center py-0.5 text-xs font-mono font-bold rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-500" />
                                        <button
                                            type="button"
                                            wire:click="incrementQty('{{ $id }}')"
                                            class="size-6 flex items-center justify-center rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 font-bold transition">
                                            +
                                        </button>
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-right font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <button
                                        type="button"
                                        wire:click="removeItem('{{ $id }}')"
                                        class="text-zinc-400 hover:text-rose-600 transition"
                                        title="Hapus baris">
                                        <flux:icon name="trash" class="size-4" />
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-zinc-400 font-sans">
                                    <div class="font-medium text-zinc-600 dark:text-zinc-400 mb-1">Keranjang masih kosong</div>
                                    <div class="text-xs text-zinc-500">Scan barcode barang belanjaan atau tekan <kbd class="font-mono font-bold text-zinc-700 dark:text-zinc-300">[F2]</kbd> untuk cari produk.</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Produk Cepat / Populer (Compact Grid) -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">Produk Cepat (Klik Tambah)</span>
                    <span class="text-[11px] text-zinc-400">8 produk aktif</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @forelse($quickProducts as $product)
                    <button
                        type="button"
                        wire:click="selectProduct('{{ $product->id }}')"
                        class="p-2 text-left rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/60 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:border-zinc-400 transition flex flex-col justify-between">
                        <div class="space-y-0.5">
                            <div class="text-xs font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                {{ $product->nama }}
                            </div>
                            <div class="text-[10px] font-mono text-zinc-400">
                                {{ $product->kode_produk }}
                            </div>
                        </div>
                        <div class="pt-1.5 flex items-center justify-between font-mono">
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($product->harga_retail, 0, ',', '.') }}
                            </span>
                            <span class="text-[10px] text-zinc-500">
                                S:{{ $product->stok }}
                            </span>
                        </div>
                    </button>
                    @empty
                    <div class="col-span-4 py-3 text-center text-xs text-zinc-400">
                        Belum ada data produk tersedia.
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- PANEL KANAN (lg:col-span-4): Total Tagihan & Konsol Kasir (Sticky) -->
        <div class="lg:col-span-4 space-y-3 sticky top-4">

            <!-- Card Utama Total Tagihan -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm space-y-4">
                <div class="text-center pb-3 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Tagihan</div>
                    <div class="text-3xl font-black font-mono text-zinc-900 dark:text-zinc-100 mt-1">
                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    </div>
                </div>

                <!-- Rincian Biaya -->
                <div class="space-y-2 text-xs font-mono">
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                        <span>Subtotal Kotor</span>
                        <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($totalDiskon > 0)
                    <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
                        <span>Potongan Diskon</span>
                        <span>- Rp {{ number_format($totalDiskon, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between text-zinc-500 text-[11px] pt-1 border-t border-zinc-100 dark:border-zinc-800">
                        <span>Total Qty</span>
                        <span>{{ count($cart) }} item ({{ collect($cart)->sum('qty') }} pcs)</span>
                    </div>
                </div>

                <!-- Tombol Eksekusi Pembayaran Kasir -->
                <div class="pt-2 space-y-2">
                    <button
                        type="button"
                        wire:click="openPayment"
                        @disabled(empty($cart))
                        class="w-full py-3 px-4 rounded text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2 shadow-sm">
                        <flux:icon name="banknotes" class="size-4" />
                        <span>Bayar Transaksi [F8]</span>
                    </button>

                    @if(count($cart) > 0)
                    <button
                        type="button"
                        wire:click="clearCart"
                        wire:confirm="Yakin ingin membersihkan seluruh item di keranjang?"
                        class="w-full py-2 px-3 rounded text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                        Batal / Kosongkan Keranjang [F9]
                    </button>
                    @endif
                </div>
            </div>

            <!-- Card Status Laci Kasir & Shift -->
            <div class="bg-white dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800 p-3.5 space-y-3 font-mono">
                <div class="flex items-center justify-between pb-2 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-1.5 font-bold text-xs text-zinc-800 dark:text-zinc-200 uppercase tracking-wide">
                        <flux:icon name="inbox-stack" class="size-4 text-zinc-500" />
                        <span>Laci Kasir (Shift)</span>
                    </div>
                    @if($activeShift)
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-semibold border border-emerald-300 dark:border-emerald-800">AKTIF</span>
                    @else
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-semibold border border-amber-300 dark:border-amber-800">BELUM BUKA</span>
                    @endif
                </div>

                @if($activeShift)
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between text-zinc-500">
                        <span>Modal Awal:</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-zinc-500">
                        <span>Penjualan Tunai:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">+ Rp {{ number_format($totalPenjualanShift, 0, ',', '.') }}</span>
                    </div>
                    @if($totalPengeluaranShift > 0)
                    <div class="flex justify-between text-zinc-500">
                        <span>Pengeluaran Kas:</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400">- Rp {{ number_format($totalPengeluaranShift, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="pt-2 border-t border-dashed border-zinc-200 dark:border-zinc-700 flex justify-between items-baseline">
                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Kas Saat Ini:</span>
                        <span class="text-base font-black text-emerald-700 dark:text-emerald-400">Rp {{ number_format($saldoKasSaatIni, 0, ',', '.') }}</span>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="confirmCloseShift"
                    class="w-full py-2 px-3 rounded text-xs font-bold text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-950/40 dark:hover:text-rose-300 border border-zinc-200 dark:border-zinc-700 transition flex items-center justify-center gap-1.5">
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5" />
                    <span>Tutup Shift & Rekap Kas [F10]</span>
                </button>
                @else
                <div class="text-xs text-zinc-500 space-y-2">
                    <p class="leading-relaxed">Shift kasir belum dibuka. Buka shift sekarang untuk mencatat modal kasir dan melacak kas laci.</p>
                    <button
                        type="button"
                        wire:click="$set('showOpenShiftModal', true)"
                        class="w-full py-2 px-3 rounded text-xs font-bold text-white bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition flex items-center justify-center gap-1.5">
                        <flux:icon name="lock-open" class="size-3.5" />
                        <span>Buka Shift Kasir Baru</span>
                    </button>
                </div>
                @endif
            </div>

            <!-- Panduan Hotkey Keyboard POS (Bukan Pill Dekoratif) -->
            <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded border border-zinc-200 dark:border-zinc-800 p-3 text-xs text-zinc-600 dark:text-zinc-400 space-y-1.5 font-mono">
                <div class="font-bold text-[11px] text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">Pintas Tombol (Keyboard)</div>
                <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-[11px]">
                    <div><span class="font-bold text-zinc-900 dark:text-zinc-100">[Enter]</span> Scan barcode</div>
                    <div><span class="font-bold text-zinc-900 dark:text-zinc-100">[F2]</span> Cari manual</div>
                    <div><span class="font-bold text-emerald-600 dark:text-emerald-400">[F8]</span> Pembayaran</div>
                    <div><span class="font-bold text-rose-600 dark:text-rose-400">[F9]</span> Kosongkan</div>
                    <div><span class="font-bold text-indigo-600 dark:text-indigo-400">[F10]</span> Tutup Shift</div>
                </div>
            </div>

        </div>

    </div>

    <!-- MODAL PEMBAYARAN KASIR (Payment Modal F8) -->
    <flux:modal wire:model="showPaymentModal" class="md:w-[480px] space-y-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon name="banknotes" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <flux:heading size="lg">Pembayaran Transaksi</flux:heading>
            </div>
            <flux:subheading>Masukkan uang tunai yang diterima dari pelanggan.</flux:subheading>
        </div>

        <!-- Box Total Tagihan -->
        <div class="p-3.5 rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-center space-y-0.5">
            <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Yang Harus Dibayar</div>
            <div class="text-2xl font-black text-zinc-900 dark:text-zinc-100 font-mono">
                Rp {{ number_format($grandTotal, 0, ',', '.') }}
            </div>
        </div>

        <!-- Tombol Pecahan Cepat Tunai -->
        <div class="space-y-1">
            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Pecahan Cepat:</label>
            <div class="grid grid-cols-3 gap-1.5">
                <button
                    type="button"
                    wire:click="setNominalBayar({{ $grandTotal }})"
                    class="py-1.5 px-2 rounded border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 text-xs font-bold text-emerald-800 dark:text-emerald-200 transition">
                    Uang Pas
                </button>
                @php
                $fractions = [10000, 20000, 50000, 100000, 200000];
                @endphp
                @foreach($fractions as $frac)
                @if($frac >= $grandTotal || $grandTotal > 50000)
                <button
                    type="button"
                    wire:click="setNominalBayar({{ $frac }})"
                    class="py-1.5 px-2 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-xs font-mono font-medium text-zinc-800 dark:text-zinc-200 transition">
                    Rp {{ number_format($frac, 0, ',', '.') }}
                </button>
                @endif
                @endforeach
            </div>
        </div>

        <!-- Input Nominal Bayar -->
        <div class="space-y-1">
            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Nominal Tunai Diterima (Rp):</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-zinc-400 font-mono text-sm">Rp</span>
                <input
                    type="number"
                    wire:model.live="nominalBayar"
                    min="{{ $grandTotal }}"
                    step="1000"
                    class="w-full py-2.5 pl-10 pr-4 text-xl font-mono font-bold rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                    placeholder="0"
                    autofocus />
            </div>
        </div>

        <!-- Box Kembalian -->
        <div class="p-3 rounded {{ (float)$nominalBayar >= $grandTotal ? 'bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700' : 'bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800' }} flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider {{ (float)$nominalBayar >= $grandTotal ? 'text-zinc-700 dark:text-zinc-300' : 'text-rose-700 dark:text-rose-300' }}">
                {{ (float)$nominalBayar >= $grandTotal ? 'Kembalian:' : 'Uang Kurang:' }}
            </span>
            <span class="text-xl font-black font-mono {{ (float)$nominalBayar >= $grandTotal ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                Rp {{ number_format(abs((float)$nominalBayar - $grandTotal), 0, ',', '.') }}
            </span>
        </div>

        <!-- Tombol Aksi Modal Pembayaran -->
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <flux:button type="button" wire:click="closePaymentModal" variant="subtle" size="sm">
                Batal (Esc)
            </flux:button>
            <flux:button
                type="button"
                wire:click="processTransaction"
                variant="primary"
                size="sm"
                class="bg-emerald-600 hover:bg-emerald-700"
                icon="check-circle"
                :disabled="(float)$nominalBayar < $grandTotal">
                Simpan & Cetak Struk
            </flux:button>
        </div>
    </flux:modal>

    <!-- MODAL CETAK STRUK PEMBAYARAN (Receipt Modal) -->
    <flux:modal wire:model="showReceiptModal" class="md:w-[440px] space-y-4">
        <div class="no-print">
            <div class="flex items-center gap-2">
                <flux:icon name="printer" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <flux:heading size="lg">Struk Transaksi Selesai</flux:heading>
            </div>
            <flux:subheading>Transaksi berhasil dibukukan. Struk kasir siap dicetak.</flux:subheading>
        </div>

        @if($lastSale)
        @php $storeProfile = \App\Models\StoreProfile::getProfile(); @endphp
        <!-- Area Preview / Cetak Struk (Thermal 58mm/80mm) -->
        <div id="receipt-print-area" class="p-4 bg-white text-zinc-900 border border-dashed border-zinc-300 rounded shadow-inner font-mono text-xs leading-relaxed space-y-2">
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
                    <span class="font-bold">{{ $lastSale->no_transaksi }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Waktu:</span>
                    <span>{{ $lastSale->waktu->format('d/m/Y H:i:s') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Kasir:</span>
                    <span>{{ $lastSale->kasir->name ?? 'Kasir' }}</span>
                </div>
            </div>

            <!-- Daftar Item Belanja -->
            <div class="space-y-1.5 py-1 border-b border-dashed border-zinc-300">
                @foreach($lastSale->items as $item)
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
                    <span>{{ $lastSale->items->count() }} jenis ({{ $lastSale->items->sum('qty') }} pcs)</span>
                </div>
                <div class="flex justify-between font-bold text-sm">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($lastSale->total, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Tunai / Bayar</span>
                    <span>Rp {{ number_format($lastSale->bayar, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between font-bold">
                    <span>Kembali</span>
                    <span>Rp {{ number_format($lastSale->kembali, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Footer Struk -->
            <div class="text-center text-[10px] text-zinc-500 pt-2 space-y-0.5">
                <div>*** TERIMA KASIH ATAS KUNJUNGAN ANDA ***</div>
                <div>{{ $storeProfile->footer_struk ?: 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan' }}</div>
            </div>
        </div>
        @endif

        <!-- Tombol Aksi Modal Struk -->
        <div class="no-print space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                <span class="font-medium">Ukuran Kertas Thermal:</span>
                <select id="pos-paper-size" class="text-xs bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded px-2.5 py-1 font-medium text-zinc-800 dark:text-zinc-200 focus:outline-none">
                    <option value="80mm" selected>Thermal 80mm (Standar POS)</option>
                    <option value="58mm">Thermal 58mm (Mini POS)</option>
                </select>
            </div>
            <div class="flex items-center justify-between gap-2">
                <flux:button type="button" wire:click="closeReceiptModal" variant="subtle" size="sm" icon="arrow-path">
                    Transaksi Baru
                </flux:button>
                <flux:button
                    type="button"
                    onclick="const s = document.getElementById('pos-paper-size')?.value || '80mm'; window.printThermalReceipt('receipt-print-area', s);"
                    variant="primary"
                    size="sm"
                    class="bg-emerald-600 hover:bg-emerald-700"
                    icon="printer">
                    Cetak Struk Sekarang
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- MODAL PENCARIAN MANUAL PRODUK (Search Modal F2) -->
    <flux:modal wire:model="showSearchModal" class="md:w-[640px] space-y-3">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon name="magnifying-glass" class="size-5 text-zinc-700 dark:text-zinc-300" />
                <flux:heading size="lg">Cari Produk Manual [F2]</flux:heading>
            </div>
            <flux:subheading>Gunakan jika barcode fisik barang rusak, kusam, atau tidak terbaca scanner.</flux:subheading>
        </div>

        <div>
            <flux:input
                wire:model.live.debounce.250ms="searchKeyword"
                placeholder="Ketik nama produk atau kode barcode..."
                icon="magnifying-glass"
                autofocus />
        </div>

        <!-- Hasil Pencarian -->
        <div class="max-h-72 overflow-y-auto divide-y divide-zinc-200 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded">
            @forelse($searchResults as $prod)
            <div class="p-2.5 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                <div>
                    <div class="font-semibold text-zinc-900 dark:text-zinc-100 text-xs">
                        {{ $prod->nama }}
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-zinc-500 font-mono">
                        <span>{{ $prod->kode_produk }}</span>
                        <span>&bull;</span>
                        <span>{{ $prod->satuan }}</span>
                        <span>&bull;</span>
                        <span class="{{ $prod->stok > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                            Stok: {{ $prod->stok }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-bold text-xs text-zinc-900 dark:text-zinc-100 font-mono">
                        Rp {{ number_format($prod->harga_retail, 0, ',', '.') }}
                    </span>
                    <button
                        type="button"
                        wire:click="selectProduct('{{ $prod->id }}')"
                        @disabled($prod->stok <= 0)
                        class="px-2.5 py-1 rounded text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 transition">
                        Pilih
                    </button>
                </div>
            </div>
            @empty
            <div class="py-6 text-center text-xs text-zinc-400">
                @if(empty($searchKeyword))
                Ketik nama barang atau kode produk di kolom pencarian.
                @else
                Tidak ditemukan produk dengan kata kunci "<strong>{{ $searchKeyword }}</strong>".
                @endif
            </div>
            @endforelse
        </div>

        <div class="flex justify-end pt-1">
            <flux:button type="button" wire:click="closeSearchModal" variant="subtle" size="sm">
                Tutup (Esc)
            </flux:button>
        </div>
    </flux:modal>

    <!-- MODAL PANDUAN SCANNER KAMERA HP (Barcode to PC) -->
    <div
        x-show="showGuide"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @keydown.window.escape="showGuide = false">
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 max-w-md w-full p-5 space-y-4 shadow-xl">
            <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="device-phone-mobile" class="size-5 text-emerald-600 dark:text-emerald-400" />
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Panduan Kamera HP (Barcode to PC)</h3>
                </div>
                <button type="button" @click="showGuide = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 text-lg font-bold">&times;</button>
            </div>

            <div class="text-xs text-zinc-600 dark:text-zinc-300 space-y-2 leading-relaxed">
                <p>
                    1. Pastikan HP dan PC kasir terhubung pada <strong>jaringan Wi-Fi yang sama</strong>.
                </p>
                <p>
                    2. Buka aplikasi <strong>Barcode to PC</strong> di Android / iOS.
                </p>
                <p>
                    3. Arahkan kamera HP ke barcode barang. Hasil scan otomatis dikirim ke kolom pemindai kasir ini dan menekan Enter.
                </p>
                <p class="text-zinc-400 text-[11px] pt-1 border-t border-zinc-100 dark:border-zinc-800">
                    *Mendukung juga scanner fisik USB/Bluetooth atau pencarian manual <strong>[F2]</strong>.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="showGuide = false" class="px-3.5 py-1.5 rounded text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition">
                    Mengerti & Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL BUKA SHIFT KASIR -->
    <flux:modal wire:model="showOpenShiftModal" class="md:w-[460px] space-y-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon name="lock-open" class="size-5 text-zinc-800 dark:text-zinc-200" />
                <flux:heading size="lg">Buka Shift Kasir Baru</flux:heading>
            </div>
            <flux:subheading>Tetapkan modal awal uang tunai di laci kasir sebelum melayani transaksi.</flux:subheading>
        </div>

        <div class="space-y-3">
            <div class="space-y-1">
                <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Pilihan Cepat Modal Awal:</label>
                <div class="grid grid-cols-4 gap-1.5 font-mono">
                    <button
                        type="button"
                        wire:click="setSaldoAwalPreset(50000)"
                        class="py-1.5 px-2 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-xs font-semibold text-zinc-800 dark:text-zinc-200 transition">
                        50rb
                    </button>
                    <button
                        type="button"
                        wire:click="setSaldoAwalPreset(100000)"
                        class="py-1.5 px-2 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-xs font-semibold text-zinc-800 dark:text-zinc-200 transition">
                        100rb
                    </button>
                    <button
                        type="button"
                        wire:click="setSaldoAwalPreset(200000)"
                        class="py-1.5 px-2 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-xs font-semibold text-zinc-800 dark:text-zinc-200 transition">
                        200rb
                    </button>
                    <button
                        type="button"
                        wire:click="setSaldoAwalPreset(500000)"
                        class="py-1.5 px-2 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-xs font-semibold text-zinc-800 dark:text-zinc-200 transition">
                        500rb
                    </button>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Nominal Modal Awal Laci (Rp):</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-zinc-400 font-mono text-sm">Rp</span>
                    <input
                        type="number"
                        wire:model="saldoAwalInput"
                        min="0"
                        step="1000"
                        class="w-full py-2.5 pl-10 pr-4 text-xl font-mono font-bold rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100"
                        placeholder="0">
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Catatan Pembukaan (Opsional):</label>
                <input
                    type="text"
                    wire:model="catatanShift"
                    placeholder="Contoh: Shift pagi kasir A"
                    class="w-full py-1.5 px-3 text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100">
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <flux:button type="button" wire:click="$set('showOpenShiftModal', false)" variant="subtle">
                Batal
            </flux:button>
            <flux:button type="button" wire:click="openShift" variant="primary">
                Buka Shift Sekarang
            </flux:button>
        </div>
    </flux:modal>

    <!-- MODAL TUTUP SHIFT & SETTLEMENT KASIR -->
    <flux:modal wire:model="showCloseShiftModal" class="md:w-[500px] space-y-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-5 text-rose-600 dark:text-rose-400" />
                <flux:heading size="lg">Tutup Shift & Rekapitulasi Kasir</flux:heading>
            </div>
            <flux:subheading>Hitung uang fisik di laci kasir dan bandingkan dengan perhitungan sistem.</flux:subheading>
        </div>

        <!-- Rekapitulasi Perhitungan Sistem Kasir -->
        <div class="p-3.5 rounded bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-2 text-xs font-mono">
            <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 pb-1 border-b border-zinc-200 dark:border-zinc-700">
                Data Perhitungan Sistem
            </div>
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <span>Modal Awal Laci:</span>
                <span class="font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <span>Total Penjualan Tunai:</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">+ Rp {{ number_format($totalPenjualanShift, 0, ',', '.') }}</span>
            </div>
            @if($totalPengeluaranShift > 0)
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <span>Total Pengeluaran Kas:</span>
                <span class="font-bold text-rose-600 dark:text-rose-400">- Rp {{ number_format($totalPengeluaranShift, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="pt-2 border-t border-dashed border-zinc-300 dark:border-zinc-600 flex justify-between items-baseline font-bold text-sm">
                <span class="text-zinc-800 dark:text-zinc-200">Kas Seharusnya di Laci:</span>
                <span class="text-zinc-900 dark:text-zinc-100 font-black">Rp {{ number_format($saldoKasSaatIni, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Input Fisik Laci Kasir -->
        <div class="space-y-3">
            <div class="space-y-1">
                <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Total Uang Fisik di Laci Kasir (Rp):</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-zinc-400 font-mono text-sm">Rp</span>
                    <input
                        type="number"
                        wire:model.live="saldoFisikInput"
                        min="0"
                        step="1000"
                        class="w-full py-2.5 pl-10 pr-4 text-xl font-mono font-bold rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100"
                        placeholder="0"
                        autofocus>
                </div>
            </div>

            @php
            $diffKas = (float) $saldoFisikInput - (float) $saldoKasSaatIni;
            @endphp

            <!-- Status Selisih Kas -->
            <div class="p-3 rounded border font-mono text-xs flex items-center justify-between {{ $diffKas == 0 ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : ($diffKas > 0 ? 'bg-blue-50 dark:bg-blue-950/40 border-blue-300 dark:border-blue-800 text-blue-800 dark:text-blue-300' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300') }}">
                <div>
                    <span class="font-bold">STATUS REKONSILIASI:</span>
                    <div class="text-[11px]">
                        @if($diffKas == 0)
                            Uang Pas / Seimbang
                        @elseif($diffKas > 0)
                            Surplus / Uang Fisik Lebih
                        @else
                            Defisit / Uang Fisik Kurang
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] uppercase tracking-wider">Selisih Kas</div>
                    <div class="text-base font-black">
                        {{ $diffKas > 0 ? '+' : '' }}Rp {{ number_format($diffKas, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Catatan Penutupan / Keterangan Selisih (Opsional):</label>
                <input
                    type="text"
                    wire:model="catatanShift"
                    placeholder="Contoh: Selisih uang receh pecahan 500"
                    class="w-full py-1.5 px-3 text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100">
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <flux:button type="button" wire:click="$set('showCloseShiftModal', false)" variant="subtle">
                Batal
            </flux:button>
            <flux:button type="button" wire:click="closeShift" variant="danger">
                Konfirmasi Tutup Shift & Cetak Rekap
            </flux:button>
        </div>
    </flux:modal>

    <!-- MODAL CETAK STRUK REKAPITULASI SHIFT KASIR -->
    <flux:modal wire:model="showShiftReceiptModal" class="md:w-[460px] space-y-4">
        <div class="no-print">
            <div class="flex items-center gap-2">
                <flux:icon name="printer" class="size-5 text-zinc-800 dark:text-zinc-200" />
                <flux:heading size="lg">Rekapitulasi Penutupan Shift</flux:heading>
            </div>
            <flux:subheading>Shift kasir telah berhasil ditutup. Struk settlement laci kasir siap dicetak.</flux:subheading>
        </div>

        @if($lastClosedShift)
        @php $storeProfile = \App\Models\StoreProfile::getProfile(); @endphp
        <!-- Area Preview & Cetak Struk Rekap Shift Kasir (Thermal 58mm/80mm) -->
        <div id="shift-receipt-print-area" class="p-4 bg-white text-zinc-900 border border-dashed border-zinc-300 rounded shadow-inner font-mono text-xs leading-relaxed space-y-2">
            <!-- Header Toko -->
            <div class="text-center space-y-0.5 pb-2 border-b border-dashed border-zinc-400">
                <div class="text-base font-bold tracking-wider">{{ $storeProfile->nama_toko }}</div>
                <div class="text-[10px] text-zinc-600">REKAPITULASI SHIFT KASIR</div>
                <div class="text-[10px] text-zinc-500">{{ now()->translatedFormat('d F Y H:i') }}</div>
            </div>

            <!-- Detail Shift -->
            <div class="text-[11px] space-y-0.5 pb-1 border-b border-dashed border-zinc-300">
                <div class="flex justify-between">
                    <span>ID Shift:</span>
                    <span class="font-bold">#{{ $lastClosedShift->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Kasir:</span>
                    <span class="font-bold">{{ $lastClosedShift->user->name ?? auth()->user()->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Waktu Buka:</span>
                    <span>{{ $lastClosedShift->waktu_buka?->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Waktu Tutup:</span>
                    <span>{{ $lastClosedShift->waktu_tutup?->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <!-- Rincian Aliran Kas Laci -->
            <div class="space-y-1 py-1 border-b border-dashed border-zinc-300 text-xs">
                <div class="flex justify-between">
                    <span>Modal Awal:</span>
                    <span>Rp {{ number_format($lastClosedShift->saldo_awal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Total Penjualan:</span>
                    <span>Rp {{ number_format($lastClosedShift->total_penjualan, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Total Pengeluaran:</span>
                    <span>Rp {{ number_format($lastClosedShift->total_pengeluaran, 0, ',', '.') }}</span>
                </div>
                <div class="pt-1 border-t border-dashed border-zinc-200 flex justify-between font-bold">
                    <span>Kas Seharusnya:</span>
                    <span>Rp {{ number_format($lastClosedShift->saldo_akhir_sistem, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Hasil Fisik & Selisih -->
            <div class="space-y-1 py-1 border-b border-dashed border-zinc-400 text-xs">
                <div class="flex justify-between font-bold">
                    <span>Uang Fisik di Laci:</span>
                    <span>Rp {{ number_format($lastClosedShift->saldo_akhir_fisik, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between font-bold">
                    <span>Selisih (Fisik - Kas):</span>
                    <span>{{ $lastClosedShift->selisih > 0 ? '+' : '' }}Rp {{ number_format($lastClosedShift->selisih, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-[11px]">
                    <span>Status Rekonsiliasi:</span>
                    <span class="font-bold uppercase">{{ $lastClosedShift->status_selisih }}</span>
                </div>
                @if($lastClosedShift->catatan)
                <div class="text-[10px] text-zinc-600 pt-1">
                    <span>Catatan: {{ $lastClosedShift->catatan }}</span>
                </div>
                @endif
            </div>

            <!-- Lembar Tanda Tangan Kasir & Supervisor -->
            <div class="pt-3 pb-1 grid grid-cols-2 text-center text-[10px] text-zinc-600">
                <div class="space-y-8">
                    <div>Kasir Yang Menyerahkan</div>
                    <div class="font-bold underline">{{ $lastClosedShift->user->name ?? 'Kasir' }}</div>
                </div>
                <div class="space-y-8">
                    <div>Supervisor / Finance</div>
                    <div class="font-bold">( .................... )</div>
                </div>
            </div>

            <!-- Footer Struk Rekap -->
            <div class="text-center text-[10px] text-zinc-500 pt-2 border-t border-dashed border-zinc-300">
                <div>*** DOKUMEN SETTLEMENT LACI KASIR RESMI ***</div>
            </div>
        </div>
        @endif

        <!-- Tombol Aksi Modal Rekap Shift -->
        <div class="no-print flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <flux:button type="button" wire:click="closeShiftReceiptModal" variant="subtle">
                Selesai & Buka Shift Baru
            </flux:button>
            <flux:button type="button" onclick="window.print()" variant="primary" class="gap-1.5">
                <flux:icon name="printer" class="size-4" />
                <span>Cetak Rekap Struk</span>
            </flux:button>
        </div>
    </flux:modal>

</div>
