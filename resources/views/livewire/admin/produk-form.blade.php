<div
    x-data="{
        cameraActive: false,
        cameraList: [],
        selectedCameraId: localStorage.getItem('mapos_camera_id') || '',
        html5QrCode: null,
        scannerLoading: false,
        flashSuccess: false,
        lastScannedCode: null,
        cameraError: null,

        async toggleCameraScanner() {
            if (this.cameraActive) {
                await this.stopCamera();
            } else {
                await this.startCamera();
            }
        },

        async startCamera() {
            this.cameraError = null;
            this.scannerLoading = true;

            try {
                if (!window.Html5Qrcode) {
                    throw new Error('Modul pemindai kamera belum terpasang atau sedang memuat.');
                }

                const devices = await window.Html5Qrcode.getCameras();
                if (!devices || devices.length === 0) {
                    throw new Error('Tidak ditemukan perangkat kamera (Webcam / Phone Link) pada komputer Anda.');
                }

                this.cameraList = devices;

                let targetId = this.selectedCameraId;
                const found = devices.find(d => d.id === targetId);
                if (!found) {
                    const phoneCam = devices.find(d => 
                        d.label.toLowerCase().includes('virtual') || 
                        d.label.toLowerCase().includes('phone') || 
                        d.label.toLowerCase().includes('link')
                    );
                    targetId = phoneCam ? phoneCam.id : devices[0].id;
                    this.selectedCameraId = targetId;
                    localStorage.setItem('mapos_camera_id', targetId);
                }

                this.cameraActive = true;
                this.$nextTick(async () => {
                    await this.runScannerInstance(targetId);
                });
            } catch (err) {
                console.error('Camera init error:', err);
                this.cameraError = err.message || 'Gagal memulai kamera.';
                this.cameraActive = false;
            } finally {
                this.scannerLoading = false;
            }
        },

        async runScannerInstance(cameraId) {
            try {
                if (this.html5QrCode) {
                    try { await this.html5QrCode.stop(); } catch(e){}
                }

                this.html5QrCode = new window.Html5Qrcode('product-camera-viewport');
                const config = {
                    fps: 15,
                    qrbox: { width: 260, height: 160 },
                    aspectRatio: 1.333333,
                    formatsToSupport: [
                        window.Html5QrcodeSupportedFormats.EAN_13,
                        window.Html5QrcodeSupportedFormats.EAN_8,
                        window.Html5QrcodeSupportedFormats.UPC_A,
                        window.Html5QrcodeSupportedFormats.UPC_E,
                        window.Html5QrcodeSupportedFormats.CODE_128,
                        window.Html5QrcodeSupportedFormats.CODE_39,
                        window.Html5QrcodeSupportedFormats.QR_CODE
                    ]
                };

                await this.html5QrCode.start(
                    cameraId,
                    config,
                    (decodedText) => {
                        this.onCodeDetected(decodedText);
                    },
                    () => {}
                );
            } catch (err) {
                console.error('Html5Qrcode error:', err);
                this.cameraError = 'Gagal mengakses feed kamera. Pastikan izin kamera aktif.';
            }
        },

        async switchCamera(newCameraId) {
            this.selectedCameraId = newCameraId;
            localStorage.setItem('mapos_camera_id', newCameraId);
            if (this.cameraActive) {
                await this.runScannerInstance(newCameraId);
            }
        },

        onCodeDetected(code) {
            this.lastScannedCode = code;

            if (window.playPosBeep) {
                window.playPosBeep('success');
            }

            this.flashSuccess = true;
            setTimeout(() => { this.flashSuccess = false; }, 400);

            $wire.setScannedBarcode(code);
        },

        async stopCamera() {
            if (this.html5QrCode) {
                try {
                    await this.html5QrCode.stop();
                    await this.html5QrCode.clear();
                } catch(e) {}
                this.html5QrCode = null;
            }
            this.cameraActive = false;
            this.cameraError = null;
        }
    }"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- CSS Style Viewport Kamera -->
    <style>
        #product-camera-viewport video {
            width: 100% !important;
            height: 200px !important;
            object-fit: cover !important;
            border-radius: 0.5rem;
        }
        #product-camera-viewport img,
        #product-camera-viewport span {
            display: none !important;
        }
    </style>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ $isEditing ? 'Perbaiki Data Produk' : 'Tambah Produk Baru (F8)' }}</flux:heading>
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

                <!-- Kode Barcode & Generator & Kamera Scanner -->
                <div class="md:col-span-2 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-zinc-500">Kode Barcode / Kode Produk *</label>
                        <!-- Tombol Toggle Kamera HP -->
                        <button
                            type="button"
                            @click="toggleCameraScanner()"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold transition border shadow-xs"
                            :class="cameraActive ? 'bg-rose-50 dark:bg-rose-950/60 border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-300' : 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40'"
                        >
                            <template x-if="cameraActive">
                                <span class="flex items-center gap-1.5">
                                    <span class="size-2 rounded-full bg-rose-500 animate-pulse"></span>
                                    Tutup Kamera
                                </span>
                            </template>
                            <template x-if="!cameraActive">
                                <span class="flex items-center gap-1.5">
                                    <flux:icon name="camera" class="size-3.5" />
                                    📷 Scan via Kamera HP (Phone Link)
                                </span>
                            </template>
                        </button>
                    </div>

                    <!-- Jendela Live Viewport Kamera Tambah Produk -->
                    <div x-show="cameraActive" x-collapse class="space-y-2">
                        <div class="p-3 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 space-y-2">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <label class="font-semibold text-zinc-600 dark:text-zinc-300 whitespace-nowrap">Pilih Kamera:</label>
                                <select
                                    x-model="selectedCameraId"
                                    @change="switchCamera($event.target.value)"
                                    class="w-full text-xs py-1.5 px-2 rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 truncate focus:outline-none focus:border-emerald-500"
                                >
                                    <template x-for="cam in cameraList" :key="cam.id">
                                        <option :value="cam.id" x-text="cam.label || ('Kamera ' + cam.id)"></option>
                                    </template>
                                </select>
                            </div>

                            <div
                                class="relative rounded-lg overflow-hidden bg-black flex items-center justify-center min-h-[200px] border-2 transition duration-300"
                                :class="flashSuccess ? 'border-emerald-400 ring-4 ring-emerald-400/50' : 'border-zinc-700'"
                            >
                                <div id="product-camera-viewport" class="w-full h-full"></div>

                                <div
                                    x-show="flashSuccess"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="absolute inset-0 bg-emerald-500/30 pointer-events-none flex items-center justify-center"
                                >
                                    <span class="bg-emerald-600 text-white font-bold font-mono text-xs px-3 py-1.5 rounded-full shadow-lg">
                                        ✓ BARCODE BERHASIL DISCAN
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-zinc-500 font-mono">
                                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                    <span class="size-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                    Arahkan kamera ke barcode kemasan produk baru
                                </span>
                                <span x-show="lastScannedCode" class="text-zinc-700 dark:text-zinc-300 font-bold" x-text="'Terdeteksi: ' + lastScannedCode"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Error Kamera -->
                    <div x-show="cameraError" x-cloak class="p-2.5 rounded-lg bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-xs text-rose-700 dark:text-rose-300 flex items-center justify-between">
                        <span x-text="cameraError"></span>
                        <button type="button" @click="cameraError = null" class="font-bold underline text-rose-600 ml-2">Tutup</button>
                    </div>

                    <!-- Input Barcode Teks & Tombol Buat Internal -->
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            wire:model.live.debounce.400ms="kode_produk" 
                            placeholder="Scan dengan scanner USB / kamera HP, atau ketik manual..." 
                            class="flex-1 font-mono text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:border-emerald-500" 
                        />
                        <flux:button type="button" wire:click="generateInternalBarcode" variant="filled" icon="qr-code">
                            Buat Kode Internal
                        </flux:button>
                    </div>
                    @if($barcodeNotice)
                        <div class="text-xs font-mono {{ str_contains($barcodeNotice, '✅') ? 'text-emerald-600 dark:text-emerald-400' : (str_contains($barcodeNotice, '⚠️') ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500') }}">
                            {{ $barcodeNotice }}
                        </div>
                    @endif
                    @error('kode_produk') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Nama Produk -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Nama Produk Lengkap *</label>
                    <input type="text" wire:model="nama" placeholder="Contoh: Indomie Mi Goreng Spesial 85g" class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                    @error('nama') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </flux:card>

        <!-- 2. Harga & Diskon (SmartPos Format) -->
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">2. Penetapan Harga & Diskon</flux:heading>
                @if($margin > 0)
                    <flux:badge color="emerald" size="sm">Margin Retail: +{{ $margin }}%</flux:badge>
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
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Harga Grosir 1 (Opsional)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-zinc-400">Rp</span>
                        <input type="number" step="any" wire:model="harga_grosir_1" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-8 pr-3 py-2 text-zinc-600 dark:text-zinc-300" />
                    </div>
                </div>

                <!-- Grosir 2 -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Harga Grosir 2 (Opsional)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-zinc-400">Rp</span>
                        <input type="number" step="any" wire:model="harga_grosir_2" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-8 pr-3 py-2 text-zinc-600 dark:text-zinc-300" />
                    </div>
                </div>

                <!-- Grosir 3 -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Harga Grosir 3 (Opsional)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-zinc-400">Rp</span>
                        <input type="number" step="any" wire:model="harga_grosir_3" class="w-full text-sm font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-8 pr-3 py-2 text-zinc-600 dark:text-zinc-300" />
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- 3. Stok Etalase, Gudang & Batas Minimum (SmartPos Format) -->
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

