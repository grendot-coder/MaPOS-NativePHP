<div>
    <!-- CSS Cetak Khusus (@media print) -->
    <style>
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            flux-sidebar, header, nav, .no-print, [data-flux-sidebar], flux-header {
                display: none !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-area {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            @page {
                size: auto;
                margin: 5mm;
            }
        }
    </style>

    <!-- AREA NON-PRINT: Tampilan Pengaturan & Pemilihan Produk -->
    <div class="no-print space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl" level="1">Cetak Label Barcode Produk</flux:heading>
                    <flux:badge color="indigo" size="sm">SmartPos Acuan</flux:badge>
                </div>
                <flux:subheading>Pilih produk, tentukan jumlah stiker label, dan cetak langsung ke printer label / kertas stiker.</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                @if(!empty($printQueue))
                    <flux:button type="button" onclick="window.print()" variant="primary" icon="printer">
                        Cetak {{ $totalStickers }} Label Sekarang
                    </flux:button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- 1. Pemilihan Produk (Katalog Pencarian) -->
            <div class="lg:col-span-1 space-y-4">
                <flux:card class="space-y-4">
                    <flux:heading size="md">1. Pilih Produk</flux:heading>
                    <flux:separator />

                    <!-- Filter Golongan -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Golongan</label>
                        <select wire:model.live="selectedGolongan" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                            <option value="">-- Semua Golongan --</option>
                            @foreach($golongans as $g)
                                <option value="{{ $g->id }}">[{{ $g->kode_golongan }}] {{ $g->nama_golongan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pencarian -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Cari Barcode / Nama</label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Ketik nama atau scan barcode..."
                            class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200"
                        />
                    </div>

                    <div class="flex justify-between items-center pt-1">
                        <span class="text-xs text-zinc-500">Hasil: {{ count($availableProducts) }} Produk</span>
                        <flux:button type="button" wire:click="addAllFiltered" variant="subtle" size="xs" icon="plus-circle">
                            Tambah Semua Hasil
                        </flux:button>
                    </div>

                    <!-- Daftar Hasil Pencarian Produk -->
                    <div class="space-y-2 max-h-80 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($availableProducts as $prod)
                            <div class="pt-2 flex items-center justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-medium truncate" title="{{ $prod->nama }}">{{ $prod->nama }}</div>
                                    <div class="text-[10px] font-mono text-zinc-400">
                                        {{ $prod->kode_produk }} &bull; Rp {{ number_format($prod->harga_retail, 0, ',', '.') }}
                                    </div>
                                </div>
                                <flux:button type="button" wire:click="addToQueue('{{ $prod->id }}')" variant="filled" size="xs" icon="plus">
                                    Pilih
                                </flux:button>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-zinc-400">
                                Tidak ada produk ditemukan.
                            </div>
                        @endforelse
                    </div>
                </flux:card>

                <!-- Pengaturan Format Kertas Label -->
                <flux:card class="space-y-4 bg-zinc-50 dark:bg-zinc-800/40">
                    <flux:heading size="md">Format Label & Opsi</flux:heading>
                    <flux:separator />

                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Tata Letak Kolom</label>
                        <select wire:model.live="labelFormat" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                            <option value="grid3">3 Kolom (Kertas Stiker A4 / Tom & Jerry)</option>
                            <option value="grid2">2 Kolom (Label Sedang 50x30mm)</option>
                            <option value="thermal">1 Kolom (Roll Thermal Barcode 40x30mm)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Nama Toko Header</label>
                        <input type="text" wire:model.live="storeName" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    </div>

                    <div class="space-y-2 pt-1 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="showStoreName" class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500" />
                            <span>Tampilkan Nama Toko</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="showPrice" class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500" />
                            <span>Tampilkan Harga Retail</span>
                        </label>
                    </div>
                </flux:card>
            </div>

            <!-- 2. Antrean Cetak & Live Preview -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Daftar Antrean -->
                <flux:card class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="md">Daftar Antrean Cetak ({{ count($printQueue) }} Produk)</flux:heading>
                            <flux:text size="xs" class="text-zinc-500">Tentukan jumlah lembar stiker untuk masing-masing barang.</flux:text>
                        </div>
                        @if(!empty($printQueue))
                            <flux:button type="button" wire:click="clearQueue" variant="ghost" size="xs" class="text-rose-500">
                                Kosongkan Antrean
                            </flux:button>
                        @endif
                    </div>
                    <flux:separator />

                    @if(empty($printQueue))
                        <div class="py-8 text-center text-sm text-zinc-400">
                            Antrean cetak masih kosong. Silakan pilih produk dari kolom kiri untuk mulai membuat label.
                        </div>
                    @else
                        <div class="space-y-2 max-h-56 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($printQueue as $id => $item)
                                <div class="pt-2 flex items-center justify-between gap-3 text-xs">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium truncate">{{ $item['nama'] }}</div>
                                        <div class="font-mono text-zinc-400">{{ $item['kode_produk'] }} &bull; Rp {{ number_format($item['harga_retail'], 0, ',', '.') }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-zinc-500">Jumlah Cetak:</span>
                                        <input 
                                            type="number" 
                                            min="1" 
                                            max="100" 
                                            value="{{ $item['copies'] }}" 
                                            wire:change="updateCopies('{{ $id }}', $event.target.value)"
                                            class="w-16 font-mono text-center text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 py-1"
                                        />
                                        <span class="text-zinc-400">stiker</span>
                                        <flux:button type="button" wire:click="removeFromQueue('{{ $id }}')" variant="ghost" size="xs" icon="x-mark" class="text-rose-500" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:card>

                <!-- Pratinjau Lembar Label (Live Preview) -->
                <flux:card class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="md">Pratinjau Lembar Cetak Label ({{ $totalStickers }} Stiker)</flux:heading>
                        @if(!empty($printQueue))
                            <flux:button type="button" onclick="window.print()" variant="primary" size="sm" icon="printer">
                                Cetak Label
                            </flux:button>
                        @endif
                    </div>
                    <flux:separator />

                    @if(empty($printQueue))
                        <div class="py-12 text-center text-sm text-zinc-400">
                            Pilih produk di atas untuk melihat pratinjau lembar stiker label barcode di sini.
                        </div>
                    @else
                        <!-- Preview Box Container -->
                        <div class="p-4 bg-zinc-100 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-x-auto max-h-[500px]">
                            <!-- Lembar Kertas Stiker Preview -->
                            <div class="bg-white p-4 shadow-sm mx-auto text-black border border-zinc-300 {{ $labelFormat === 'grid3' ? 'max-w-2xl' : ($labelFormat === 'grid2' ? 'max-w-md' : 'max-w-xs') }}">
                                <div class="grid {{ $labelFormat === 'grid3' ? 'grid-cols-3' : ($labelFormat === 'grid2' ? 'grid-cols-2' : 'grid-cols-1') }} gap-2.5">
                                    @foreach($printQueue as $item)
                                        @for($i = 0; $i < $item['copies']; $i++)
                                            <div class="border border-zinc-300 border-dashed rounded p-1.5 text-center flex flex-col justify-between items-center bg-white min-h-[90px]">
                                                @if($showStoreName && $storeName)
                                                    <div class="text-[9px] font-bold tracking-wider uppercase text-zinc-700 leading-none">
                                                        {{ $storeName }}
                                                    </div>
                                                @endif
                                                
                                                <div class="text-[10px] font-semibold text-zinc-900 leading-tight line-clamp-1 w-full px-0.5" title="{{ $item['nama'] }}">
                                                    {{ $item['nama'] }}
                                                </div>

                                                <div class="w-full flex justify-center py-0.5 barcode-svg-small">
                                                    {!! $item['svg'] !!}
                                                </div>

                                                <div class="text-[9px] font-mono font-bold tracking-widest text-zinc-900 leading-none">
                                                    {{ $item['kode_produk'] }}
                                                </div>

                                                @if($showPrice)
                                                    <div class="text-[10px] font-bold font-mono text-zinc-950 mt-0.5 leading-none">
                                                        Rp {{ number_format($item['harga_retail'], 0, ',', '.') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endfor
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </flux:card>
            </div>
        </div>
    </div>

    <!-- AREA PRINT BERSAPUT KHUSUS PRINTER (@page) -->
    <div class="hidden print-area">
        @if(!empty($printQueue))
            <div class="grid {{ $labelFormat === 'grid3' ? 'grid-cols-3 gap-2' : ($labelFormat === 'grid2' ? 'grid-cols-2 gap-2' : 'grid-cols-1 gap-1.5') }} w-full">
                @foreach($printQueue as $item)
                    @for($i = 0; $i < $item['copies']; $i++)
                        <div class="border border-zinc-400 rounded p-1 text-center flex flex-col justify-between items-center bg-white break-inside-avoid" style="page-break-inside: avoid; height: 110px;">
                            @if($showStoreName && $storeName)
                                <div class="text-[9px] font-bold tracking-wider uppercase text-zinc-800 leading-none">
                                    {{ $storeName }}
                                </div>
                            @endif
                            
                            <div class="text-[10px] font-bold text-black leading-tight line-clamp-1 w-full px-1">
                                {{ $item['nama'] }}
                            </div>

                            <div class="w-full flex justify-center py-1">
                                {!! $item['svg'] !!}
                            </div>

                            <div class="text-[9px] font-mono font-bold tracking-widest text-black leading-none">
                                {{ $item['kode_produk'] }}
                            </div>

                            @if($showPrice)
                                <div class="text-[11px] font-extrabold font-mono text-black leading-none mt-0.5">
                                    Rp {{ number_format($item['harga_retail'], 0, ',', '.') }}
                                </div>
                            @endif
                        </div>
                    @endfor
                @endforeach
            </div>
        @endif
    </div>
</div>
