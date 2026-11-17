<div class="space-y-5">
    <!-- Header Halaman: Bersih & Profesional -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl" level="1">Profil Toko & Struk Kasir</flux:heading>
                <span class="text-xs font-mono px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                    SETTING RETAIL
                </span>
            </div>
            <flux:subheading class="mt-0.5">
                Konfigurasi identitas gerai, kontak layanan, dan format cetak nota kasir thermal.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded text-xs font-bold text-white bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition shadow-sm disabled:opacity-50">
                <flux:icon name="check" class="size-4" />
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </div>

    <!-- Layout Grid: 7 Kolom Form Pengaturan | 5 Kolom Live Preview Struk Thermal -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- FORM PENGATURAN PROFIL TOKO (lg:col-span-7) -->
        <form wire:submit="save" class="lg:col-span-7 space-y-4">

            <!-- Card: Identitas Utama Toko -->
            <div class="bg-white dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800 p-4 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:icon name="building-storefront" class="size-4 text-zinc-500" />
                    <h2 class="text-xs font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wide">
                        Informasi Utama Gerai
                    </h2>
                </div>

                <div class="space-y-3">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Nama Toko / Minimarket: <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="nama_toko"
                            placeholder="Contoh: MAPOS MINIMARKET"
                            class="w-full py-2 px-3 text-sm rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100 font-medium">
                        @error('nama_toko')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Slogan / Deskripsi Baris Ke-2:
                        </label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="slogan"
                            placeholder="Contoh: Sistem Kasir & Manajemen POS"
                            class="w-full py-1.5 px-3 text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100">
                        @error('slogan')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Nomor Telepon / WhatsApp:
                            </label>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="telepon"
                                placeholder="Contoh: (085) 555-555-555"
                                class="w-full py-1.5 px-3 text-xs font-mono rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100">
                            @error('telepon')
                                <p class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Alamat Email:
                            </label>
                            <input
                                type="email"
                                wire:model.live.debounce.300ms="email"
                                placeholder="Contoh: kasir@mapos.id"
                                class="w-full py-1.5 px-3 text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100">
                            @error('email')
                                <p class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Alamat Lengkap Toko -->
            <div class="bg-white dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800 p-4 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:icon name="map-pin" class="size-4 text-zinc-500" />
                    <h2 class="text-xs font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wide">
                        Alamat Fisik Toko
                    </h2>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Alamat Lengkap (Tercetak di Struk):
                    </label>
                    <textarea
                        wire:model.live.debounce.300ms="alamat"
                        rows="2"
                        placeholder="Contoh: Jl. Soedirman No. 10 - Telp: (085) 555-555-555"
                        class="w-full py-2 px-3 text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100"></textarea>
                    @error('alamat')
                        <p class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Card: Konfigurasi Struk Thermal Kasir -->
            <div class="bg-white dark:bg-zinc-900 rounded border border-zinc-200 dark:border-zinc-800 p-4 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:icon name="printer" class="size-4 text-zinc-500" />
                    <h2 class="text-xs font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wide">
                        Format Struk Nota Kasir
                    </h2>
                </div>

                <div class="space-y-3">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Catatan Kaki Struk (Footer Nota):
                        </label>
                        <textarea
                            wire:model.live.debounce.300ms="footer_struk"
                            rows="2"
                            placeholder="Contoh: Barang yang sudah dibeli tidak dapat ditukar/dikembalikan. Terima kasih atas kunjungan Anda!"
                            class="w-full py-2 px-3 text-xs rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100"></textarea>
                        @error('footer_struk')
                            <p class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Lebar Kertas Printer Kasir Default:
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2.5 p-3 rounded border cursor-pointer transition {{ $ukuran_kertas === '58mm' ? 'bg-zinc-50 dark:bg-zinc-800/80 border-zinc-900 dark:border-zinc-100' : 'border-zinc-200 dark:border-zinc-700' }}">
                                <input
                                    type="radio"
                                    wire:model.live="ukuran_kertas"
                                    value="58mm"
                                    class="text-zinc-900 dark:text-zinc-100 focus:ring-0">
                                <div>
                                    <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 font-mono">58mm (Kecil)</div>
                                    <div class="text-[11px] text-zinc-500">Printer thermal portable/Bluetooth</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded border cursor-pointer transition {{ $ukuran_kertas === '80mm' ? 'bg-zinc-50 dark:bg-zinc-800/80 border-zinc-900 dark:border-zinc-100' : 'border-zinc-200 dark:border-zinc-700' }}">
                                <input
                                    type="radio"
                                    wire:model.live="ukuran_kertas"
                                    value="80mm"
                                    class="text-zinc-900 dark:text-zinc-100 focus:ring-0">
                                <div>
                                    <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 font-mono">80mm (Standar POS)</div>
                                    <div class="text-[11px] text-zinc-500">Printer kasir desktop USB/LAN</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="px-5 py-2.5 rounded text-xs font-bold text-white bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition shadow-sm disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove>Simpan Profil Toko</span>
                    <span wire:loading class="inline-flex items-center gap-1.5">
                        <svg class="animate-spin size-3" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </form>

        <!-- PRATINJAU STRUK THERMAL REAL-TIME (lg:col-span-5) -->
        <div class="lg:col-span-5 space-y-3">
            <div class="flex items-center justify-between pb-1">
                <div class="flex items-center gap-1.5 text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">
                    <flux:icon name="eye" class="size-4 text-zinc-500" />
                    <span>Pratinjau Struk Kasir</span>
                </div>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                    KERTAS {{ strtoupper($ukuran_kertas) }}
                </span>
            </div>

            <!-- Slip Struk Thermal Kertas -->
            <div class="bg-white text-zinc-900 border border-dashed border-zinc-300 dark:border-zinc-600 rounded p-4 font-mono text-xs leading-relaxed space-y-2 shadow-sm {{ $ukuran_kertas === '80mm' ? 'max-w-[360px]' : 'max-w-[300px]' }} mx-auto">
                <!-- Header Toko -->
                <div class="text-center space-y-0.5 pb-2 border-b border-dashed border-zinc-400">
                    <div class="text-sm font-bold tracking-wider uppercase">
                        {{ filled($nama_toko) ? $nama_toko : 'NAMA TOKO ANDA' }}
                    </div>
                    @if(filled($slogan))
                    <div class="text-[10px] text-zinc-600">
                        {{ $slogan }}
                    </div>
                    @endif
                    <div class="text-[10px] text-zinc-500">
                        {{ filled($alamat) ? $alamat : 'Alamat gerai toko Anda' }}
                    </div>
                    @if(filled($telepon))
                    <div class="text-[10px] text-zinc-500">
                        Telp: {{ $telepon }}
                    </div>
                    @endif
                </div>

                <!-- Info Sample Transaksi -->
                <div class="text-[10px] space-y-0.5 pb-1 border-b border-dashed border-zinc-300 text-zinc-600">
                    <div class="flex justify-between">
                        <span>No: TRX-SAMPLE-0001</span>
                        <span>Kasir: {{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ now()->format('d/m/Y H:i') }}</span>
                        <span>Tunai</span>
                    </div>
                </div>

                <!-- Daftar Sample Item Belanja -->
                <div class="space-y-1 py-1 border-b border-dashed border-zinc-300 text-xs">
                    <div>
                        <div class="font-semibold text-zinc-800">Minyak Goreng 1L</div>
                        <div class="flex justify-between text-[11px] text-zinc-600">
                            <span>1 x 18.000</span>
                            <span class="font-bold text-zinc-900">Rp 18.000</span>
                        </div>
                    </div>
                    <div>
                        <div class="font-semibold text-zinc-800">Gula Pasir 1kg</div>
                        <div class="flex justify-between text-[11px] text-zinc-600">
                            <span>2 x 15.000</span>
                            <span class="font-bold text-zinc-900">Rp 30.000</span>
                        </div>
                    </div>
                </div>

                <!-- Rincian Sample Total -->
                <div class="space-y-0.5 pt-1 pb-1.5 border-b border-dashed border-zinc-400 text-xs">
                    <div class="flex justify-between font-bold">
                        <span>TOTAL</span>
                        <span>Rp 48.000</span>
                    </div>
                    <div class="flex justify-between text-[11px]">
                        <span>Bayar Tunai</span>
                        <span>Rp 50.000</span>
                    </div>
                    <div class="flex justify-between font-bold text-[11px]">
                        <span>Kembali</span>
                        <span>Rp 2.000</span>
                    </div>
                </div>

                <!-- Footer Struk Custom Toko -->
                <div class="text-center text-[10px] text-zinc-500 pt-1.5 space-y-0.5">
                    <div>*** TERIMA KASIH ATAS KUNJUNGAN ANDA ***</div>
                    @if(filled($footer_struk))
                    <div class="italic text-[9px] text-zinc-600">
                        {{ $footer_struk }}
                    </div>
                    @endif
                </div>
            </div>

            <div class="text-[11px] text-zinc-500 dark:text-zinc-400 p-2.5 rounded bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-800 leading-relaxed font-mono">
                <span class="font-bold text-zinc-700 dark:text-zinc-300">[i] Informasi:</span>
                Perubahan pada halaman ini otomatis tersimpan ke sistem dan diterapkan pada seluruh cetakan struk kasir dan dokumen rekap laporan.
            </div>
        </div>

    </div>
</div>
