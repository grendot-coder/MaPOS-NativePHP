<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ $isEditing ? 'Perbaiki Data Produk' : 'Tambah Produk Baru' }}</flux:heading>
            <flux:subheading>Lengkapi identitas produk, barcode, harga beli & jual, serta alokasi stok etalase dan gudang.</flux:subheading>
        </div>
        <div>
            <flux:button href="{{ route('admin.produk.index') }}" variant="subtle" icon="arrow-left" wire:navigate>
                Kembali ke Daftar
            </flux:button>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- 1. Identitas Dasar & Barcode -->
        <flux:card class="space-y-4">
            <flux:heading size="lg">1. Barcode & Identitas Produk</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Golongan -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Golongan Produk *</label>
                    <select wire:model.live="golongan_id" class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                        <option value="">-- Pilih Golongan --</option>
                        @foreach($golongans as $g)
                        <option value="{{ $g->id }}">[{{ $g->kode_golongan }}] {{ $g->nama_golongan }}</option>
                        @endforeach
                    </select>
                    @error('golongan_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Satuan -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Satuan Produk *</label>
                    <input type="text" wire:model="satuan" placeholder="pcs, bks, botol, pack, kg..." class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    @error('satuan') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Kode Barcode & Generator -->
                <div class="md:col-span-2 space-y-1">
                    <label class="block text-xs font-medium text-zinc-500">Kode Barcode / Kode Produk *</label>
                    <div class="flex gap-2">
                        <input
                            id="input-kode-produk"
                            type="text"
                            wire:model.live.debounce.300ms="kode_produk"
                            @keydown.enter.prevent="document.getElementById('input-nama-produk')?.focus()"
                            placeholder="Scan barcode barang atau ketik kode produk..."
                            class="flex-1 font-mono text-sm rounded-lg border-2 border-emerald-500/30 dark:border-emerald-500/20 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200 focus:border-emerald-500 focus:outline-none"
                            {{ !$isEditing ? 'autofocus' : '' }} />
                        <flux:button type="button" wire:click="generateInternalBarcode" variant="filled" icon="qr-code">
                            Buat Kode Internal
                        </flux:button>
                    </div>
                    @if($barcodeNotice)
                    <div class="flex items-center gap-1.5 text-xs font-mono {{ $barcodeNoticeType === 'warning' ? 'text-amber-600 dark:text-amber-400 font-semibold' : ($barcodeNoticeType === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500') }}">
                        @if($barcodeNoticeType === 'warning')
                        <flux:icon name="exclamation-triangle" class="size-3.5 shrink-0 text-amber-500" />
                        @elseif($barcodeNoticeType === 'success')
                        <flux:icon name="check-circle" class="size-3.5 shrink-0 text-emerald-500" />
                        @else
                        <flux:icon name="information-circle" class="size-3.5 shrink-0 text-zinc-400" />
                        @endif
                        <span>{{ $barcodeNotice }}</span>
                    </div>
                    @endif
                    @error('kode_produk') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Nama Produk -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Nama Produk Lengkap *</label>
                    <input
                        id="input-nama-produk"
                        type="text"
                        wire:model="nama"
                        placeholder="Contoh: Indomie Mi Goreng Spesial 85g"
                        class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    @error('nama') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </flux:card>

        <!-- 2. Harga & Diskon -->
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">2. Penetapan Harga & Diskon</flux:heading>
                @if($margin > 0)
                <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">Margin: +{{ $margin }}%</span>
                @endif
            </div>
            <flux:separator />

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <!-- Harga Beli -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Harga Beli (Modal) *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-zinc-400">Rp</span>
                        <input type="number" step="any" wire:model.live.debounce.400ms="harga_beli" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-8 pr-3 py-2" />
                    </div>
                    @error('harga_beli') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Harga Retail -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Harga Retail (Jual Kasir) *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-zinc-400">Rp</span>
                        <input type="number" step="any" wire:model.live.debounce.400ms="harga_retail" class="w-full text-sm font-mono font-bold text-emerald-600 dark:text-emerald-400 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-8 pr-3 py-2" />
                    </div>
                    @error('harga_retail') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Diskon -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Diskon Penjualan (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="diskon" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-3 pr-7 py-2" />
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-zinc-400">%</span>
                    </div>
                    @error('diskon') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Grosir 1 -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-zinc-500">Harga Grosir 1 & Min. Qty</label>
                    <div class="grid grid-cols-5 gap-1.5">
                        <div class="relative col-span-3">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs text-zinc-400">Rp</span>
                            <input type="number" step="any" wire:model="harga_grosir_1" placeholder="Harga" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-7 pr-2 py-2 text-zinc-600 dark:text-zinc-300" />
                        </div>
                        <div class="relative col-span-2">
                            <input type="number" min="2" wire:model="min_qty_grosir_1" placeholder="Min Qty" title="Minimal Qty agar Harga Grosir 1 aktif" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-2 py-2 text-zinc-600 dark:text-zinc-300" />
                        </div>
                    </div>
                    @error('min_qty_grosir_1') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Grosir 2 -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-zinc-500">Harga Grosir 2 & Min. Qty</label>
                    <div class="grid grid-cols-5 gap-1.5">
                        <div class="relative col-span-3">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs text-zinc-400">Rp</span>
                            <input type="number" step="any" wire:model="harga_grosir_2" placeholder="Harga" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-7 pr-2 py-2 text-zinc-600 dark:text-zinc-300" />
                        </div>
                        <div class="relative col-span-2">
                            <input type="number" min="2" wire:model="min_qty_grosir_2" placeholder="Min Qty" title="Minimal Qty agar Harga Grosir 2 aktif" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-2 py-2 text-zinc-600 dark:text-zinc-300" />
                        </div>
                    </div>
                    @error('min_qty_grosir_2') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Grosir 3 -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-zinc-500">Harga Grosir 3 & Min. Qty</label>
                    <div class="grid grid-cols-5 gap-1.5">
                        <div class="relative col-span-3">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs text-zinc-400">Rp</span>
                            <input type="number" step="any" wire:model="harga_grosir_3" placeholder="Harga" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-7 pr-2 py-2 text-zinc-600 dark:text-zinc-300" />
                        </div>
                        <div class="relative col-span-2">
                            <input type="number" min="2" wire:model="min_qty_grosir_3" placeholder="Min Qty" title="Minimal Qty agar Harga Grosir 3 aktif" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-2 py-2 text-zinc-600 dark:text-zinc-300" />
                        </div>
                    </div>
                    @error('min_qty_grosir_3') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Periode Promo Diskon (Opsional) -->
            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-800 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Promo Diskon Berlaku Mulai (Opsional)</label>
                    <input type="date" wire:model="promo_mulai" class="w-full text-xs font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    <span class="text-[11px] text-zinc-400">Kosongkan jika diskon berlaku terus tanpa jadwal.</span>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Promo Diskon Berakhir Sampai (Opsional)</label>
                    <input type="date" wire:model="promo_sampai" class="w-full text-xs font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    @error('promo_sampai') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </flux:card>

        <!-- 3. Stok Etalase, Gudang & Batas Minimum -->
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">3. Inventaris & Pengaturan Stok</flux:heading>
                <div class="text-sm font-medium">
                    Total Stok Fisik: <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $totalStokHitung }}</span> {{ $satuan }}
                </div>
            </div>
            <flux:separator />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Stok Etalase (Pajangan) *</label>
                    <input type="number" wire:model.live="stok_etalase" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    @error('stok_etalase') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Stok Gudang (Cadangan) *</label>
                    <input type="number" wire:model.live="stok_gudang" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    @error('stok_gudang') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Batas Minimum Stok (Peringatan) *</label>
                    <input type="number" wire:model="min_stok" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    <span class="text-xs text-zinc-400">Sistem memberi peringatan jika total &le; batas ini.</span>
                    @error('min_stok') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </flux:card>

        <!-- Tombol Aksi Simpan -->
        <div class="flex items-center justify-end gap-3">
            <flux:button href="{{ route('admin.produk.index') }}" variant="ghost" wire:navigate>
                Batal
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ $isEditing ? 'Simpan Perubahan' : 'Tambahkan Produk' }}
            </flux:button>
        </div>
    </form>
</div>