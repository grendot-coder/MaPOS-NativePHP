<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Master Produk</flux:heading>
            </div>
            <flux:subheading>Tabel data produk lengkap: kode barcode, harga beli/retail/grosir, dan pemantauan stok etalase & gudang.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.produk.create') }}" variant="primary" icon="plus" wire:navigate>
                Tambah Produk
            </flux:button>
        </div>
    </div>

    <flux:card class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Filter Golongan</label>
                <select wire:model.live="selectedGolongan" class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                    <option value="">– Semua Golongan –</option>
                    @foreach($golongans as $g)
                    <option value="{{ $g->id }}">[{{ $g->kode_golongan }}] {{ $g->nama_golongan }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-zinc-500 mb-1">Cari Barcode / Nama Produk</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Scan atau ketik kode produk / nama barang..."
                        class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-9 pr-4 py-2 text-zinc-800 dark:text-zinc-200 placeholder-zinc-400" />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                        <flux:icon name="magnifying-glass" class="size-4" />
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Batas Baris (Limit)</label>
                <select wire:model.live="perPage" class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                    <option value="15">15 Baris</option>
                    <option value="25">25 Baris (Default)</option>
                    <option value="50">50 Baris</option>
                    <option value="100">100 Baris</option>
                    <option value="200">200 Baris</option>
                </select>
            </div>
        </div>
    </flux:card>

    <!-- Tabel Data Produk MaPOS -->
    <flux:card class="p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 uppercase font-semibold">
                    <tr>
                        <th class="py-3 px-3 text-center">No</th>
                        <th class="py-3 px-3 cursor-pointer hover:text-indigo-600" wire:click="sort('kode_produk')">
                            Kode Barcode
                            @if($sortBy === 'kode_produk')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>
                        <th class="py-3 px-3 cursor-pointer hover:text-indigo-600" wire:click="sort('nama')">
                            Nama Produk
                            @if($sortBy === 'nama')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </th>
                        <th class="py-3 px-3">Golongan</th>
                        <th class="py-3 px-3 text-center">Satuan</th>
                        <th class="py-3 px-3 text-right">Harga Beli</th>
                        <th class="py-3 px-3 text-right text-emerald-600 dark:text-emerald-400">Harga Retail</th>
                        <th class="py-3 px-3 text-right">Grosir 1</th>
                        <th class="py-3 px-3 text-right">Grosir 2</th>
                        <th class="py-3 px-3 text-right">Grosir 3</th>
                        <th class="py-3 px-3 text-center">Diskon</th>
                        <th class="py-3 px-3 text-right">Etalase</th>
                        <th class="py-3 px-3 text-right">Gudang</th>
                        <th class="py-3 px-3 text-right font-bold">Total Stok</th>
                        <th class="py-3 px-3 text-right">Min Stok</th>
                        <th class="py-3 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($products as $index => $p)
                    @php
                    $isLowStock = $p->stok <= $p->min_stok;
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60 {{ $isLowStock ? 'bg-amber-50/40 dark:bg-amber-950/20' : '' }}">
                            <td class="py-2.5 px-3 text-center text-zinc-400">
                                {{ $products->firstItem() + $index }}
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                <button type="button" wire:click="showBarcode('{{ $p->id }}')" class="inline-flex items-center gap-1.5 font-mono font-medium text-xs text-zinc-800 dark:text-zinc-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition cursor-pointer group" title="Klik untuk pratinjau barcode">
                                    <flux:icon name="qr-code" class="size-3.5 text-zinc-400 group-hover:text-indigo-500" />
                                    <span>{{ $p->kode_produk }}</span>
                                </button>
                            </td>
                            <td class="py-2.5 px-3 font-medium text-zinc-900 dark:text-zinc-100 max-w-xs truncate" title="{{ $p->nama }}">
                                {{ $p->nama }}
                            </td>
                            <td class="py-2.5 px-3 text-zinc-500">
                                <span class="text-xs">[{{ $p->golongan->kode_golongan }}] {{ $p->golongan->nama_golongan }}</span>
                            </td>
                            <td class="py-2.5 px-3 text-center text-zinc-500">
                                {{ $p->satuan }}
                            </td>
                            <td class="py-2.5 px-3 text-right text-zinc-500 font-mono">
                                Rp {{ number_format($p->harga_beli, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($p->harga_retail, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right text-zinc-400 font-mono">
                                {{ $p->harga_grosir_1 ? 'Rp ' . number_format($p->harga_grosir_1, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right text-zinc-400 font-mono">
                                {{ $p->harga_grosir_2 ? 'Rp ' . number_format($p->harga_grosir_2, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right text-zinc-400 font-mono">
                                {{ $p->harga_grosir_3 ? 'Rp ' . number_format($p->harga_grosir_3, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if($p->diskon > 0)
                                <span class="font-mono font-bold text-rose-600 dark:text-rose-400">{{ $p->diskon }}%</span>
                                @else
                                <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-zinc-600 dark:text-zinc-300">
                                {{ $p->stok_etalase }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-zinc-600 dark:text-zinc-300">
                                {{ $p->stok_gudang }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold {{ $isLowStock ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                {{ $p->stok }}
                                @if($isLowStock)
                                <span class="inline-flex items-center ml-1 text-amber-500" title="Stok kritis &le; minimum">
                                    <flux:icon name="exclamation-triangle" class="size-3.5 inline-block" />
                                </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right text-zinc-400 font-mono">
                                {{ $p->min_stok }}
                            </td>
                            <td class="py-2.5 px-3 text-center space-x-1">
                                <flux:button href="{{ route('admin.barcode.print-label', ['product_id' => $p->id]) }}" size="xs" variant="subtle" icon="printer" wire:navigate title="Cetak Label Barcode" />
                                <flux:button href="{{ route('admin.produk.edit', $p->id) }}" size="xs" variant="subtle" icon="pencil-square" wire:navigate title="Edit Produk" />
                                <flux:button wire:click="delete('{{ $p->id }}')" wire:confirm="Yakin ingin menghapus produk {{ $p->nama }}?" size="xs" variant="danger" icon="trash" title="Hapus Produk" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="16" class="py-12 text-center text-zinc-500">
                                Tidak ada data produk yang cocok dengan pencarian / filter Anda.
                            </td>
                        </tr>
                        @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
            {{ $products->links() }}
        </div>
    </flux:card>

    <!-- Modal Pratinjau Barcode Produk -->
    <flux:modal wire:model="showBarcodeModal" class="md:w-96 text-center space-y-4">
        <div>
            <flux:heading size="lg">{{ $previewNama }}</flux:heading>
            <flux:subheading>{{ $previewHarga }}</flux:subheading>
        </div>

        <div class="p-4 bg-white rounded-xl border border-zinc-200 inline-block mx-auto text-black">
            @if($previewSvg)
            <div class="flex justify-center">
                {!! $previewSvg !!}
            </div>
            <div class="mt-2 text-sm font-mono font-bold tracking-widest text-zinc-900">
                {{ $previewBarcode }}
            </div>
            @endif
        </div>

        <div class="text-xs text-zinc-400">
            Barcode vektor SVG tajam siap dipindai menggunakan scanner USB/Bluetooth.
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:modal.close>
                <flux:button variant="ghost">Tutup</flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>
</div>