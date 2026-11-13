<div
    x-data="{
        focusScanner() {
            this.$nextTick(() => {
                const el = document.getElementById('barcode-input-field');
                if (el) el.focus();
            });
        }
    }"
    x-init="focusScanner()"
    @keydown.window.f2.prevent="$wire.openSearchModal()"
    @keydown.window.f8.prevent="$wire.openPayment()"
    @keydown.window.f9.prevent="if(confirm('Kosongkan keranjang belanja?')) { $wire.clearCart(); }"
    class="space-y-4"
>
    <!-- CSS Cetak Struk Khusus (@media print) -->
    <style>
        @media print {
            body {
                background: white !important;
                color: black !important;
            }

            flux-sidebar,
            header,
            nav,
            .no-print,
            [data-flux-sidebar],
            flux-header,
            .modal-backdrop,
            flux-modal {
                display: none !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
            }

            #receipt-print-area {
                display: block !important;
                position: fixed;
                left: 0;
                top: 0;
                width: 78mm;
                margin: 0;
                padding: 4mm;
                font-family: 'Courier New', Courier, monospace;
                font-size: 11px;
                line-height: 1.3;
                background: white !important;
                color: black !important;
                box-shadow: none !important;
                border: none !important;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>

    <!-- Header Kasir & Shortcut Info -->
    <div class="no-print flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Point of Sale (Kasir)</flux:heading>
                <flux:badge color="emerald" size="sm">Kasir Siap</flux:badge>
            </div>
            <flux:subheading>
                Kasir: <strong>{{ auth()->user()->name }}</strong> &bull;
                No. TRX: <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ $nextTransactionNumber }}</span> &bull;
                {{ now()->translatedFormat('l, d F Y') }}
            </flux:subheading>
        </div>

        <!-- Tombol Pintas Keyboard Kasir -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 text-xs font-mono text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                <kbd class="font-bold text-zinc-900 dark:text-zinc-100">Enter</kbd> Scan Barcode
            </span>
            <button
                type="button"
                wire:click="openSearchModal"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-xs font-mono text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 transition"
            >
                <kbd class="font-bold text-blue-600 dark:text-blue-400">F2</kbd> Cari Barang
            </button>
            <button
                type="button"
                wire:click="openPayment"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-xs font-mono text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition"
            >
                <kbd class="font-bold text-emerald-600 dark:text-emerald-400">F8</kbd> Bayar
            </button>
            <button
                type="button"
                wire:click="clearCart"
                wire:confirm="Kosongkan seluruh keranjang belanja saat ini?"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-xs font-mono text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition"
            >
                <kbd class="font-bold text-rose-600 dark:text-rose-400">F9</kbd> Bersihkan
            </button>
        </div>
    </div>

    <!-- Grid 2 Kolom: Kiri (Scanner & Quick Products) - Kanan (Keranjang & Pembayaran) -->
    <div class="no-print grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- KOLOM KIRI (lg:col-span-5): Barcode Scanner & Quick Products -->
        <div class="lg:col-span-5 space-y-4">
            
            <!-- Box Pemindai Barcode (Scanner Input) -->
            <flux:card class="p-4 bg-white dark:bg-zinc-900 border-2 border-emerald-500/30 dark:border-emerald-500/20 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="qr-code" class="size-5 text-emerald-600 dark:text-emerald-400" />
                        <span class="font-bold text-sm text-zinc-800 dark:text-zinc-200">Pemindai Barcode</span>
                    </div>
                    <span class="text-xs text-zinc-500 font-mono">Autofocus Aktif</span>
                </div>

                <form wire:submit.prevent="scanBarcode" @submit="focusScanner()" class="space-y-2">
                    <div class="relative">
                        <input
                            id="barcode-input-field"
                            wire:model="barcodeInput"
                            type="text"
                            placeholder="Arahkan scanner atau ketik barcode..."
                            class="w-full py-3 px-4 pl-11 text-base font-mono rounded-lg border-2 border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition shadow-inner"
                            autocomplete="off"
                            autofocus
                        />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                            <flux:icon name="bars-3-bottom-left" class="size-5" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:button type="submit" variant="primary" class="flex-1 bg-emerald-600 hover:bg-emerald-700" icon="plus">
                            Tambah (Enter)
                        </flux:button>
                        <flux:button type="button" wire:click="openSearchModal" variant="subtle" icon="magnifying-glass">
                            Cari (F2)
                        </flux:button>
                    </div>
                </form>
            </flux:card>

            <!-- Box Produk Populer / Cepat (Quick Add) -->
            <flux:card class="p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="sparkles" class="size-4 text-amber-500" />
                        <span class="font-bold text-sm text-zinc-800 dark:text-zinc-200">Produk Cepat (Favorit)</span>
                    </div>
                    <span class="text-xs text-zinc-400">Klik untuk tambah</span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    @forelse($quickProducts as $product)
                        <button
                            type="button"
                            wire:click="selectProduct('{{ $product->id }}')"
                            class="p-2.5 text-left rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/60 hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30 transition group flex flex-col justify-between"
                        >
                            <div class="space-y-0.5">
                                <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 line-clamp-2">
                                    {{ $product->nama }}
                                </div>
                                <div class="text-[11px] font-mono text-zinc-400">
                                    {{ $product->kode_produk }}
                                </div>
                            </div>
                            <div class="pt-2 flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    Rp {{ number_format($product->harga_retail, 0, ',', '.') }}
                                </span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-200/80 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                    Stok: {{ $product->stok }}
                                </span>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-2 py-4 text-center text-xs text-zinc-400">
                            Belum ada produk berstok tersedia.
                        </div>
                    @endforelse
                </div>
            </flux:card>

            <!-- Card Petunjuk Penggunaan Scanner Kasir -->
            <div class="p-3.5 rounded-lg bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200/60 dark:border-blue-900/40 text-xs text-blue-900 dark:text-blue-200 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <flux:icon name="information-circle" class="size-4 text-blue-600 dark:text-blue-400" />
                    Panduan Barcode Scanner
                </div>
                <p class="text-blue-800/80 dark:text-blue-300/80 text-[11px] leading-relaxed">
                    Scanner barcode fisik (USB/Wireless) akan otomatis mengetik angka kode dan menekan <strong>Enter</strong>. Jika barcode barang kusam, gunakan tombol <strong>Cari (F2)</strong>.
                </p>
            </div>
        </div>

        <!-- KOLOM KANAN (lg:col-span-7): Keranjang Belanja & Panel Pembayaran -->
        <div class="lg:col-span-7 space-y-4">
            
            <!-- Card Keranjang Belanja -->
            <flux:card class="p-0 overflow-hidden shadow-sm">
                <!-- Header Keranjang -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="shopping-cart" class="size-5 text-zinc-700 dark:text-zinc-300" />
                        <span class="font-bold text-zinc-900 dark:text-zinc-100">Keranjang Belanja</span>
                        <flux:badge size="sm" color="emerald">{{ count($cart) }} Item</flux:badge>
                    </div>

                    @if(count($cart) > 0)
                        <flux:button
                            type="button"
                            wire:click="clearCart"
                            wire:confirm="Yakin ingin membersihkan seluruh item di keranjang?"
                            size="sm"
                            variant="danger"
                            icon="trash"
                        >
                            Kosongkan (F9)
                        </flux:button>
                    @endif
                </div>

                <!-- Tabel Item Keranjang Belanja -->
                <div class="overflow-x-auto max-h-[380px] overflow-y-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-100 dark:bg-zinc-800/90 text-zinc-700 dark:text-zinc-300 text-xs font-semibold uppercase sticky top-0 z-10">
                            <tr>
                                <th class="py-2.5 px-3 w-10 text-center">#</th>
                                <th class="py-2.5 px-3">Produk & Barcode</th>
                                <th class="py-2.5 px-3 text-right">Harga</th>
                                <th class="py-2.5 px-3 text-center w-28">Qty</th>
                                <th class="py-2.5 px-3 text-right">Subtotal</th>
                                <th class="py-2.5 px-3 w-10 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @php $no = 1; @endphp
                            @forelse($cart as $id => $item)
                                <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition">
                                    <td class="py-2.5 px-3 text-center text-xs text-zinc-400">
                                        {{ $no++ }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $item['nama'] }}
                                        </div>
                                        <div class="flex items-center gap-2 text-xs font-mono text-zinc-400">
                                            <span>{{ $item['kode_produk'] }}</span>
                                            <span>&bull;</span>
                                            <span>{{ $item['satuan'] }}</span>
                                            @if($item['diskon'] > 0)
                                                <span class="text-rose-600 dark:text-rose-400 font-semibold">Diskon {{ $item['diskon'] }}%</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                        @if($item['diskon'] > 0)
                                            <div class="text-[11px] line-through text-zinc-400">
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
                                    <td class="py-2.5 px-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                type="button"
                                                wire:click="decrementQty('{{ $id }}')"
                                                class="size-7 flex items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 font-bold transition"
                                            >
                                                &minus;
                                            </button>
                                            <input
                                                type="number"
                                                min="1"
                                                max="{{ $item['stok_max'] }}"
                                                value="{{ $item['qty'] }}"
                                                wire:change="updateQty('{{ $id }}', $event.target.value)"
                                                class="w-12 text-center py-1 text-xs font-mono font-bold rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-emerald-500"
                                            />
                                            <button
                                                type="button"
                                                wire:click="incrementQty('{{ $id }}')"
                                                class="size-7 flex items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 font-bold transition"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                        Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <button
                                            type="button"
                                            wire:click="removeItem('{{ $id }}')"
                                            class="text-zinc-400 hover:text-rose-600 transition"
                                            title="Hapus baris"
                                        >
                                            <flux:icon name="trash" class="size-4" />
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-zinc-400 space-y-2">
                                        <div class="mx-auto size-12 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-400">
                                            <flux:icon name="shopping-bag" class="size-6" />
                                        </div>
                                        <div class="font-medium text-zinc-600 dark:text-zinc-400">Keranjang masih kosong</div>
                                        <div class="text-xs text-zinc-400 max-w-xs mx-auto">
                                            Scan barcode barang belanjaan pelanggan atau pilih dari daftar produk cepat di sebelah kiri.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Rincian & Grand Total -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/80 border-t border-zinc-200 dark:border-zinc-800 space-y-3">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                            <span>Subtotal Kotor</span>
                            <span class="font-mono">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($totalDiskon > 0)
                            <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
                                <span>Potongan Diskon</span>
                                <span class="font-mono">- Rp {{ number_format($totalDiskon, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Grand Total Display Besar -->
                    <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Tagihan</div>
                            <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                                Rp {{ number_format($grandTotal, 0, ',', '.') }}
                            </div>
                        </div>

                        <!-- Tombol Bayar -->
                        <div>
                            <flux:button
                                type="button"
                                wire:click="openPayment"
                                variant="primary"
                                class="px-8 py-3.5 text-base font-bold bg-emerald-600 hover:bg-emerald-700 shadow-md"
                                icon="banknotes"
                                :disabled="empty($cart)"
                            >
                                Bayar (F8)
                            </flux:button>
                        </div>
                    </div>
                </div>
            </flux:card>

        </div>
    </div>

    <!-- MODAL PEMBAYARAN KASIR (Payment Modal) -->
    <flux:modal wire:model="showPaymentModal" class="md:w-[500px] space-y-5">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon name="banknotes" class="size-6 text-emerald-600 dark:text-emerald-400" />
                <flux:heading size="lg">Pembayaran Transaksi</flux:heading>
            </div>
            <flux:subheading>Masukkan uang tunai yang diterima dari pelanggan.</flux:subheading>
        </div>

        <!-- Box Total Tagihan -->
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-center space-y-1">
            <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Total Yang Harus Dibayar</div>
            <div class="text-3xl font-black text-emerald-700 dark:text-emerald-300 font-mono">
                Rp {{ number_format($grandTotal, 0, ',', '.') }}
            </div>
        </div>

        <!-- Tombol Pecahan Cepat -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Pecahan Cepat:</label>
            <div class="grid grid-cols-3 gap-2">
                <button
                    type="button"
                    wire:click="setNominalBayar({{ $grandTotal }})"
                    class="py-2 px-2.5 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-100/60 dark:bg-emerald-900/40 hover:bg-emerald-200 text-xs font-bold text-emerald-800 dark:text-emerald-200 transition"
                >
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
                            class="py-2 px-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-xs font-mono font-semibold text-zinc-800 dark:text-zinc-200 transition"
                        >
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
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-zinc-400">Rp</span>
                <input
                    type="number"
                    wire:model.live="nominalBayar"
                    min="{{ $grandTotal }}"
                    step="1000"
                    class="w-full py-2.5 pl-10 pr-4 text-xl font-mono font-bold rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    placeholder="0"
                    autofocus
                />
            </div>
        </div>

        <!-- Box Kembalian -->
        <div class="p-3.5 rounded-lg {{ (float)$nominalBayar >= $grandTotal ? 'bg-emerald-100/70 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800' : 'bg-rose-100/70 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-800' }} flex items-center justify-between">
            <span class="text-sm font-semibold {{ (float)$nominalBayar >= $grandTotal ? 'text-emerald-800 dark:text-emerald-200' : 'text-rose-800 dark:text-rose-200' }}">
                {{ (float)$nominalBayar >= $grandTotal ? 'Kembalian:' : 'Uang Kurang:' }}
            </span>
            <span class="text-xl font-black font-mono {{ (float)$nominalBayar >= $grandTotal ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                Rp {{ number_format(abs((float)$nominalBayar - $grandTotal), 0, ',', '.') }}
            </span>
        </div>

        <!-- Tombol Aksi Modal Pembayaran -->
        <div class="flex items-center justify-end gap-2 pt-2">
            <flux:button type="button" wire:click="closePaymentModal" variant="subtle">
                Batal (Esc)
            </flux:button>
            <flux:button
                type="button"
                wire:click="processTransaction"
                variant="primary"
                class="bg-emerald-600 hover:bg-emerald-700"
                icon="check-circle"
                :disabled="(float)$nominalBayar < $grandTotal"
            >
                Simpan & Cetak Struk
            </flux:button>
        </div>
    </flux:modal>

    <!-- MODAL CETAK STRUK PEMBAYARAN (Receipt Modal) -->
    <flux:modal wire:model="showReceiptModal" class="md:w-[440px] space-y-4">
        <div class="no-print">
            <div class="flex items-center gap-2">
                <flux:icon name="printer" class="size-6 text-emerald-600 dark:text-emerald-400" />
                <flux:heading size="lg">Struk Transaksi Selesai</flux:heading>
            </div>
            <flux:subheading>Transaksi berhasil dibukukan. Struk kasir siap dicetak.</flux:subheading>
        </div>

        @if($lastSale)
            <!-- Area Preview / Cetak Struk (Thermal 58mm/80mm) -->
            <div id="receipt-print-area" class="p-4 bg-white text-zinc-900 border border-dashed border-zinc-300 rounded-lg shadow-inner font-mono text-xs leading-relaxed space-y-2">
                <!-- Header Toko -->
                <div class="text-center space-y-0.5 pb-2 border-b border-dashed border-zinc-400">
                    <div class="text-base font-bold tracking-wider">MAPOS MINIMARKET</div>
                    <div class="text-[10px] text-zinc-600">Sistem Kasir & Manajemen POS</div>
                    <div class="text-[10px] text-zinc-500">Jl. Pelajar PKL No. 10 - Telp: (021) 555-MAPOS</div>
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
                        </div>
                    @endforeach
                </div>

                <!-- Rincian Total, Bayar & Kembali -->
                <div class="space-y-1 pt-1 pb-2 border-b border-dashed border-zinc-400 text-xs">
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
                    <div>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</div>
                </div>
            </div>
        @endif

        <!-- Tombol Aksi Modal Struk -->
        <div class="no-print flex items-center justify-between pt-2">
            <flux:button type="button" wire:click="closeReceiptModal" variant="subtle" icon="arrow-path">
                Transaksi Baru (Tutup)
            </flux:button>
            <flux:button type="button" onclick="window.print()" variant="primary" class="bg-emerald-600 hover:bg-emerald-700" icon="printer">
                Cetak Struk Sekarang
            </flux:button>
        </div>
    </flux:modal>

    <!-- MODAL PENCARIAN MANUAL PRODUK (Search Modal F2) -->
    <flux:modal wire:model="showSearchModal" class="md:w-[640px] space-y-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon name="magnifying-glass" class="size-6 text-blue-600 dark:text-blue-400" />
                <flux:heading size="lg">Cari Produk Manual (F2)</flux:heading>
            </div>
            <flux:subheading>Gunakan jika barcode fisik barang rusak, kusam, atau tidak terbaca scanner.</flux:subheading>
        </div>

        <div>
            <flux:input
                wire:model.live.debounce.250ms="searchKeyword"
                placeholder="Ketik nama produk atau kode barcode..."
                icon="magnifying-glass"
                autofocus
            />
        </div>

        <!-- Hasil Pencarian -->
        <div class="max-h-72 overflow-y-auto divide-y divide-zinc-200 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg">
            @forelse($searchResults as $prod)
                <div class="p-3 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm">
                            {{ $prod->nama }}
                        </div>
                        <div class="flex items-center gap-2 text-xs text-zinc-400 font-mono">
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
                        <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                            Rp {{ number_format($prod->harga_retail, 0, ',', '.') }}
                        </span>
                        <flux:button
                            type="button"
                            wire:click="selectProduct('{{ $prod->id }}')"
                            size="sm"
                            variant="primary"
                            icon="plus"
                            :disabled="$prod->stok <= 0"
                        >
                            Pilih
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-zinc-400">
                    @if(empty($searchKeyword))
                        Ketik nama barang atau kode produk di kolom pencarian di atas.
                    @else
                        Tidak ditemukan produk dengan kata kunci "<strong>{{ $searchKeyword }}</strong>".
                    @endif
                </div>
            @endforelse
        </div>

        <div class="flex justify-end pt-2">
            <flux:button type="button" wire:click="closeSearchModal" variant="subtle">
                Tutup
            </flux:button>
        </div>
    </flux:modal>

</div>
