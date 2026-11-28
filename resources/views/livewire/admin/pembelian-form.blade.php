<div class="space-y-6 pb-20">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.pembelian.index') }}" class="text-xs font-mono text-zinc-500 hover:text-indigo-600 transition flex items-center gap-1" wire:navigate>
                    <flux:icon name="arrow-left" class="size-3" />
                    <span>Kembali ke Daftar Faktur</span>
                </a>
            </div>
            <flux:heading size="xl" level="1">Input Faktur Pembelian & Kulakan</flux:heading>
            <flux:subheading>Catat barang masuk dari supplier. Stok produk akan bertambah otomatis ke lokasi yang dipilih.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button type="button" wire:click="openSupplierModal" variant="subtle" icon="user-plus" size="sm">
                + Supplier Baru
            </flux:button>
        </div>
    </div>

    @error('faktur')
    <div class="p-3 rounded-lg border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 text-xs font-medium">
        {{ $message }}
    </div>
    @enderror

    <!-- Kartu Informasi Faktur Header -->
    <flux:card class="p-4 space-y-4">
        <div class="text-xs font-bold uppercase tracking-wider text-zinc-500 border-b border-zinc-200 dark:border-zinc-800 pb-2">
            1. Informasi Faktur & Supplier
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <!-- No Faktur -->
            <div>
                <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    No. Faktur / Referensi <span class="text-rose-500">*</span>
                </label>
                <flux:input wire:model="no_faktur" placeholder="Contoh: PB-20261117-0001 / INV-9921" size="sm" class="font-mono" required />
                @error('no_faktur') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <!-- Tanggal Faktur -->
            <div>
                <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Tanggal Faktur <span class="text-rose-500">*</span>
                </label>
                <input type="date" wire:model="tanggal" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-800 dark:text-zinc-200 font-mono" required />
                @error('tanggal') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <!-- Supplier -->
            <div>
                <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Supplier / Agen <span class="text-rose-500">*</span>
                </label>
                <div class="space-y-1.5">
                    <select wire:model.live="supplier_id" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-800 dark:text-zinc-200">
                        <option value="">-- Ketik Bebas / Supplier Umum --</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->nama }}</option>
                        @endforeach
                    </select>
                    @if(empty($supplier_id))
                    <flux:input wire:model="nama_supplier" placeholder="Nama Supplier / Toko Kulakan" size="sm" />
                    @endif
                </div>
                @error('nama_supplier') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <!-- Status Pembayaran -->
            <div>
                <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Status Pembayaran <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2 rounded border border-zinc-300 dark:border-zinc-700 cursor-pointer {{ $status_pembayaran === 'lunas' ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-500/50 text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-zinc-700 dark:text-zinc-300' }}">
                        <input type="radio" wire:model.live="status_pembayaran" value="lunas" class="text-emerald-600 focus:ring-emerald-500" />
                        <span>LUNAS</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded border border-zinc-300 dark:border-zinc-700 cursor-pointer {{ $status_pembayaran === 'hutang' ? 'bg-amber-50/50 dark:bg-amber-950/20 border-amber-500/50 text-amber-700 dark:text-amber-400 font-semibold' : 'text-zinc-700 dark:text-zinc-300' }}">
                        <input type="radio" wire:model.live="status_pembayaran" value="hutang" class="text-amber-600 focus:ring-amber-500" />
                        <span>HUTANG</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 {{ $status_pembayaran === 'hutang' ? 'md:grid-cols-2' : '' }} gap-4">
            @if($status_pembayaran === 'hutang')
            <div>
                <label class="block font-medium text-amber-700 dark:text-amber-400 mb-1 text-xs">Tanggal Jatuh Tempo Hutang</label>
                <input type="date" wire:model="jatuh_tempo" class="w-full text-xs rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-800 dark:text-zinc-200 font-mono" />
            </div>
            @endif
            <div>
                <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1 text-xs">Catatan Faktur (Opsional)</label>
                <input type="text" wire:model="catatan" placeholder="Nomor resi, jatuh tempo pembayaran, kondisi barang, dsb." class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-800 dark:text-zinc-200" />
            </div>
        </div>
    </flux:card>

    <!-- Kartu Input Cepat Barang (Barcode & Pencarian Produk) -->
    <flux:card class="p-4 space-y-3">
        <div class="text-xs font-bold uppercase tracking-wider text-zinc-500 border-b border-zinc-200 dark:border-zinc-800 pb-2">
            2. Tambah Barang Masuk
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Scan Barcode (Fast Entry) -->
            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Scan Barcode Produk:
                </label>
                <form wire:submit.prevent="scanBarcode" class="flex gap-2">
                    <flux:input
                        wire:model="barcodeInput"
                        placeholder="Scan atau ketik barcode lalu tekan [Enter]..."
                        icon="qr-code"
                        class="font-mono text-xs flex-1"
                        autofocus />
                    <flux:button type="submit" variant="primary" size="sm" class="bg-indigo-600 hover:bg-indigo-700">
                        + Tambah
                    </flux:button>
                </form>
                @error('barcodeInput')
                <div class="text-rose-500 text-[11px] mt-1 flex flex-wrap items-center gap-2">
                    <span>{{ $message }}</span>
                    @if($missingBarcode)
                    <a href="{{ route('admin.produk.create', ['barcode' => $missingBarcode]) }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline" wire:navigate>
                        + Daftarkan Produk Baru ({{ $missingBarcode }}) &rarr;
                    </a>
                    @endif
                </div>
                @enderror
            </div>

            <!-- Cari Manual Nama Produk -->
            <div class="relative">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Atau Cari Nama Produk:
                </label>
                <flux:input
                    wire:model.live.debounce.250ms="searchProduct"
                    placeholder="Ketik minimal 2 huruf nama barang..."
                    icon="magnifying-glass"
                    size="sm"
                    clearable />

                @if($searchResults->isNotEmpty())
                <div class="absolute left-0 right-0 z-30 mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg max-h-60 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($searchResults as $prod)
                    <button
                        type="button"
                        wire:click="selectProduct('{{ $prod->id }}')"
                        class="w-full text-left p-2.5 hover:bg-indigo-50/50 dark:hover:bg-zinc-800 transition flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-zinc-900 dark:text-zinc-100">{{ $prod->nama }}</div>
                            <div class="font-mono text-[11px] text-zinc-500">
                                SKU: {{ $prod->kode_produk }} &bull; Modal Sekarang: Rp {{ number_format($prod->harga_beli, 0, ',', '.') }}
                            </div>
                        </div>
                        <span class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400 font-semibold">
                            + Pilih
                        </span>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </flux:card>

    <!-- Tabel Rincian Barang Faktur (High-Density Workstation Table) -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                3. Daftar Barang Faktur ({{ count($items) }} barang)
            </span>
            @error('items')
            <span class="text-rose-500 text-xs font-semibold">{{ $message }}</span>
            @enderror
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-100/70 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 font-semibold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3 w-10 text-center">#</th>
                        <th class="p-3">Produk</th>
                        <th class="p-3 text-center w-36">Lokasi Tujuan</th>
                        <th class="p-3 text-center w-28">Jumlah (Qty)</th>
                        <th class="p-3 text-right w-44">Harga Modal Satuan (Rp)</th>
                        <th class="p-3 text-center w-40">Update Master</th>
                        <th class="p-3 text-right w-40">Subtotal</th>
                        <th class="p-3 text-center w-12">Hapus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($items as $idx => $item)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                        <td class="p-3 text-center font-mono text-zinc-400">
                            {{ $idx + 1 }}
                        </td>
                        <td class="p-3">
                            <div class="font-bold text-zinc-900 dark:text-zinc-100">{{ $item['nama'] }}</div>
                            <div class="font-mono text-[11px] text-zinc-500">Barcode: {{ $item['kode_produk'] }}</div>
                        </td>
                        <td class="p-3 text-center">
                            <select
                                wire:model="items.{{ $idx }}.lokasi_tujuan"
                                class="text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-2 py-1 text-zinc-800 dark:text-zinc-200 font-mono">
                                <option value="gudang">GUDANG</option>
                                <option value="etalase">ETALASE</option>
                            </select>
                        </td>
                        <td class="p-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <input
                                    type="number"
                                    min="1"
                                    wire:change="updateQty({{ $idx }}, $event.target.value)"
                                    value="{{ $item['qty'] }}"
                                    class="w-20 text-center font-mono font-bold text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-1 text-zinc-900 dark:text-zinc-100" />
                                <span class="text-[11px] text-zinc-400">{{ $item['satuan'] }}</span>
                            </div>
                        </td>
                        <td class="p-3 text-right">
                            <div class="relative">
                                <input
                                    type="number"
                                    min="0"
                                    step="100"
                                    wire:change="updateHargaBeli({{ $idx }}, $event.target.value)"
                                    value="{{ $item['harga_beli'] }}"
                                    class="w-36 text-right font-mono font-bold text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-1 text-zinc-900 dark:text-zinc-100" />
                            </div>
                        </td>
                        <td class="p-3 text-center">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer text-[11px] text-zinc-600 dark:text-zinc-400">
                                <input
                                    type="checkbox"
                                    wire:model="items.{{ $idx }}.update_harga_beli_master"
                                    class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500" />
                                <span>Master</span>
                            </label>
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-sm text-zinc-900 dark:text-zinc-100">
                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-center">
                            <button
                                type="button"
                                wire:click="removeItem({{ $idx }})"
                                class="text-zinc-400 hover:text-rose-600 transition p-1"
                                title="Hapus Barang Dari Faktur">
                                <flux:icon name="trash" class="size-4" />
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-xs text-zinc-400">
                            Belum ada barang dalam faktur kulakan ini. Silakan scan barcode produk atau gunakan kolom pencarian di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sticky Bottom Bar (POS Workstation Total & Action Bar) -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-zinc-900/95 backdrop-blur border-t border-zinc-200 dark:border-zinc-800 p-3 shadow-lg">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <span class="text-[11px] uppercase tracking-wider text-zinc-400 block font-semibold">Total Item:</span>
                    <span class="font-mono font-bold text-sm text-zinc-800 dark:text-zinc-200">{{ count($items) }} jenis / {{ $this->totalQty }} pcs</span>
                </div>
                <div class="border-l border-zinc-200 dark:border-zinc-800 pl-6">
                    <span class="text-[11px] uppercase tracking-wider text-zinc-400 block font-semibold">Total Nilai Faktur:</span>
                    <span class="font-mono font-extrabold text-xl text-indigo-600 dark:text-indigo-400">
                        Rp {{ number_format($this->totalTagihan, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <flux:button :href="route('admin.pembelian.index')" variant="subtle" size="sm" wire:navigate>
                    Batal
                </flux:button>
                <flux:button
                    type="button"
                    wire:click="simpanFaktur"
                    variant="primary"
                    icon="check"
                    class="bg-indigo-600 hover:bg-indigo-700"
                    size="sm"
                    :disabled="empty($items)">
                    Simpan & Bukukan Faktur
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Supplier Baru -->
    @if($showSupplierModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xl w-full max-w-md p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Tambah Supplier Baru</h3>
                <button type="button" wire:click="$set('showSupplierModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>

            <form wire:submit="saveSupplier" class="space-y-3 text-xs">
                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nama Supplier / PT / Toko Agen <span class="text-rose-500">*</span></label>
                    <flux:input wire:model="newSupplierNama" placeholder="Contoh: PT. Sumber Makmur" size="sm" required />
                    @error('newSupplierNama') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">No. Telepon / WhatsApp</label>
                    <flux:input wire:model="newSupplierTelepon" placeholder="08xxxxxxxxxx" size="sm" />
                </div>

                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nama Sales / Kontak PIC</label>
                    <flux:input wire:model="newSupplierKontak" placeholder="Nama sales atau agen" size="sm" />
                </div>

                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Alamat Supplier</label>
                    <textarea wire:model="newSupplierAlamat" rows="2" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2 text-zinc-800 dark:text-zinc-200" placeholder="Alamat gudang / kantor supplier"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button type="button" wire:click="$set('showSupplierModal', false)" variant="subtle" size="sm">Batal</flux:button>
                    <flux:button type="submit" variant="primary" size="sm" class="bg-indigo-600 hover:bg-indigo-700">Simpan Supplier</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
