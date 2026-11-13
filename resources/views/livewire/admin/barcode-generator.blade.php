<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Generator & Validasi Barcode</flux:heading>
            </div>
            <flux:subheading>Uji validitas check digit EAN-13, buat kode barcode internal, dan render visual barcode siap scan.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Form Input & Generator -->
        <div class="md:col-span-1 space-y-6">
            <!-- 1. Input / Scan Barcode -->
            <flux:card class="space-y-4">
                <flux:heading size="lg">Input / Scan</flux:heading>
                <flux:separator />

                <!-- Input Langsung / Scan -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Ketik / Scan Barcode</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="barcode"
                        placeholder="Scan atau ketik kode..."
                        class="w-full font-mono text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                </div>

                <!-- Atau Pilih Produk -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Atau Pilih dari Produk</label>
                    <select wire:model.live="selectedProductId" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                        <option value="">-- Pilih Produk Terdaftar --</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->kode_produk }} - {{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </flux:card>

            <!-- 2. Pembuat Kode Internal (Format Acuan PKL) -->
            <flux:card class="space-y-4 bg-zinc-50 dark:bg-zinc-800/40">
                <div>
                    <flux:heading size="md">Buat Barcode Internal</flux:heading>
                    <flux:text size="xs" class="text-zinc-500">Standar GS1 internal (awalan 20 + 0 + kode golongan + nomor urut + check digit).</flux:text>
                </div>
                <flux:separator />

                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Pilih Golongan</label>
                    <select wire:model="selectedGolonganId" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                        @foreach($golongans as $g)
                        <option value="{{ $g->id }}">[{{ $g->kode_golongan }}] {{ $g->nama_golongan }}</option>
                        @endforeach
                    </select>
                </div>

                <flux:button type="button" wire:click="generateInternal" variant="primary" class="w-full" icon="sparkles">
                    Generate EAN-13 Internal
                </flux:button>
            </flux:card>
        </div>

        <!-- Kolom Kanan: Visual Render & Info Teknis -->
        <div class="md:col-span-2 space-y-6">
            <!-- Kartu Visual Barcode -->
            <flux:card class="p-6 text-center space-y-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div class="flex items-center gap-2">
                        <flux:icon name="qr-code" class="size-5 text-indigo-500" />
                        <span class="text-sm font-semibold">Tampilan Visual Barcode</span>
                    </div>
                    <flux:badge color="{{ $detectedType === 'EAN-13' ? 'emerald' : 'blue' }}">
                        Format: {{ $detectedType }}
                    </flux:badge>
                </div>

                <!-- Preview Area (Background Putih untuk Kontras Barcode) -->
                <div class="p-6 bg-white rounded-xl shadow-sm border border-zinc-200 inline-block mx-auto max-w-full overflow-x-auto text-black">
                    @if($barcodeSvg)
                    <div class="flex justify-center barcode-svg-container">
                        {!! $barcodeSvg !!}
                    </div>
                    <div class="mt-2 text-base font-mono font-bold tracking-widest text-zinc-900">
                        {{ chunk_split($barcode, 4, ' ') }}
                    </div>
                    @else
                    <div class="py-10 px-8 text-zinc-400 text-sm">
                        Barcode belum tersedia atau format kode tidak valid.
                    </div>
                    @endif
                </div>

                <!-- Status Validasi -->
                <div class="p-3 rounded-lg text-sm text-center {{ $isValidEan13 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                    {{ $validationMessage }}
                </div>

                <!-- Metadata Teknis Barcode -->
                <div class="grid grid-cols-3 gap-2 text-left pt-2 border-t border-zinc-100 dark:border-zinc-800 text-xs">
                    <div>
                        <span class="text-zinc-400 block">Panjang Karakter:</span>
                        <span class="font-mono font-semibold">{{ strlen($barcode) }} Digit</span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block">Check Digit:</span>
                        <span class="font-mono font-semibold">
                            @if(strlen($barcode) === 13 && ctype_digit($barcode))
                            Digit ke-13: {{ substr($barcode, -1) }} ({{ $isValidEan13 ? 'Cocok' : 'Gagal' }})
                            @else
                            Standar {{ $detectedType }}
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block">Keterbacaan Scanner:</span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">Siap Scan (USB/BT)</span>
                    </div>
                </div>
            </flux:card>

            <!-- Card Aturan Barcode SmartPos Acuan PKL -->
            <flux:card class="space-y-3 bg-zinc-50 dark:bg-zinc-800/30 text-xs text-zinc-600 dark:text-zinc-400">
                <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Guide Barcode:</flux:heading>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>EAN-13 (13 Digit)</strong>: Barcode ritel resmi atau kode internal dengan verifikasi digit pemeriksa (bobot 1 & 3).</li>
                    <li><strong>UPC-A (12 Digit)</strong>: Format standar barang impor AS/Kanada. Jika scan awal gagal, sistem mencoba tanpa awalan 0.</li>
                    <li><strong>EAN-8 (8 Digit)</strong>: Digunakan untuk kemasan produk berukuran kecil.</li>
                    <li><strong>Code 128</strong>: Format serbaguna untuk kode alfanumerik atau kode pendek khusus.</li>
                </ul>
            </flux:card>
        </div>
    </div>
</div>