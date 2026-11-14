<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @if(auth()->user()->isAdmin())
                    <flux:sidebar.group heading="Utama" class="grid">
                        <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                            Dashboard
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Referensi (SmartPos)" class="grid">
                        <flux:sidebar.item icon="cube" :href="route('admin.produk.index')" :current="request()->routeIs('admin.produk.*')" wire:navigate>
                            Master Produk
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="tag" :href="route('admin.golongan.index')" :current="request()->routeIs('admin.golongan.*')" wire:navigate>
                            Golongan Produk
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="qr-code" :href="route('admin.barcode.index')" :current="request()->routeIs('admin.barcode.index')" wire:navigate>
                            Generator Barcode
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="printer" :href="route('admin.barcode.print-label')" :current="request()->routeIs('admin.barcode.print-label')" wire:navigate>
                            Cetak Label Barcode
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Operasional" class="grid">
                        <flux:sidebar.item icon="shopping-cart" :href="route('kasir.pos')" :current="request()->routeIs('kasir.pos')" wire:navigate>
                            Layar Kasir (POS)
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Manajemen Stok" class="grid">
                        <flux:sidebar.item icon="adjustments-horizontal" :href="route('admin.stok.penyesuaian')" :current="request()->routeIs('admin.stok.penyesuaian')" wire:navigate>
                            Penyesuaian Stok
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('admin.stok.riwayat')" :current="request()->routeIs('admin.stok.riwayat')" wire:navigate>
                            Buku Kartu Stok
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Laporan" class="grid">
                        <flux:sidebar.item icon="document-chart-bar" :href="route('admin.laporan.penjualan')" :current="request()->routeIs('admin.laporan.penjualan')" wire:navigate>
                            Laporan Penjualan
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="archive-box" :href="route('admin.laporan.stok')" :current="request()->routeIs('admin.laporan.stok')" wire:navigate>
                            Laporan Stok & Aset
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @else
                    <flux:sidebar.group heading="Menu Kasir" class="grid">
                        <flux:sidebar.item icon="shopping-cart" :href="route('kasir.pos')" :current="request()->routeIs('kasir.pos')" wire:navigate>
                            Layar Kasir (POS)
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('kasir.riwayat')" :current="request()->routeIs('kasir.riwayat')" wire:navigate>
                            Riwayat Transaksi Saya
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="px-3 py-2 text-xs text-zinc-400">
                <div class="font-semibold text-zinc-500">MaPOS v1.0 (PKL)</div>
                <div>Role: <span class="capitalize font-medium text-indigo-500">{{ auth()->user()->role?->label() ?? 'Pengguna' }}</span></div>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        <script>
            /**
             * Helper cetak struk thermal MaPOS via iframe terisolasi.
             * Mencegah pemotongan tampilan dan scrollbar yang terjadi saat mencetak dari dalam modal dialog.
             */
            window.printThermalReceipt = function(elementId, paperWidth = '80mm') {
                const printArea = document.getElementById(elementId);
                if (!printArea) {
                    console.error('Area cetak struk tidak ditemukan:', elementId);
                    return;
                }

                const is58 = paperWidth === '58mm';
                const contentWidth = is58 ? '48mm' : '72mm';
                const baseFontSize = is58 ? '10px' : '11px';
                const titleFontSize = is58 ? '13px' : '15px';

                let iframe = document.getElementById('mapos-receipt-iframe');
                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.id = 'mapos-receipt-iframe';
                    iframe.style.position = 'fixed';
                    iframe.style.right = '0';
                    iframe.style.bottom = '0';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = '0';
                    document.body.appendChild(iframe);
                }

                const doc = iframe.contentWindow.document;
                doc.open();
                doc.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="utf-8">
                        <title>Struk Belanja - MaPOS</title>
                        <style>
                            @page {
                                size: ${paperWidth} auto;
                                margin: 0;
                            }
                            * {
                                box-sizing: border-box;
                                margin: 0;
                                padding: 0;
                            }
                            body {
                                font-family: 'Courier New', Courier, monospace;
                                font-size: ${baseFontSize};
                                line-height: 1.35;
                                color: #000;
                                background: #fff;
                                width: ${contentWidth};
                                max-width: ${contentWidth};
                                margin: 0 auto;
                                padding: 4mm 1.5mm;
                            }
                            .text-center { text-align: center; }
                            .text-right { text-align: right; }
                            .text-left { text-align: left; }
                            .font-bold { font-weight: bold; }
                            .font-semibold { font-weight: 600; }
                            .text-base { font-size: ${titleFontSize}; }
                            .text-sm { font-size: ${is58 ? '11px' : '12px'}; }
                            .text-xs { font-size: ${baseFontSize}; }
                            .text-\\[10px\\] { font-size: ${is58 ? '9px' : '10px'}; }
                            .text-\\[11px\\] { font-size: ${is58 ? '10px' : '11px'}; }
                            .text-\\[9px\\] { font-size: 8.5px; }
                            .tracking-wider { letter-spacing: 0.05em; }
                            .tracking-widest { letter-spacing: 0.1em; }
                            .uppercase { text-transform: uppercase; }
                            .italic { font-style: italic; }
                            .flex { display: flex; }
                            .justify-between { justify-content: space-between; }
                            .items-center { align-items: center; }
                            .border-b { border-bottom: 1px dashed #333; }
                            .border-t { border-top: 1px dashed #333; }
                            .border-dashed { border-style: dashed; }
                            .border-zinc-300, .border-zinc-400 { border-color: #333; }
                            .space-y-0\\.5 > * + * { margin-top: 2px; }
                            .space-y-1 > * + * { margin-top: 4px; }
                            .space-y-1\\.5 > * + * { margin-top: 6px; }
                            .space-y-2 > * + * { margin-top: 8px; }
                            .pb-1 { padding-bottom: 4px; }
                            .pb-2 { padding-bottom: 6px; }
                            .pt-1 { padding-top: 4px; }
                            .pt-2 { padding-top: 6px; }
                            .py-1 { padding-top: 4px; padding-bottom: 4px; }
                            .p-4 { padding: 0; }
                            .shadow-inner, .rounded-lg { box-shadow: none !important; border-radius: 0 !important; }
                        </style>
                    </head>
                    <body>
                        ${printArea.innerHTML}
                    </body>
                    </html>
                `);
                doc.close();

                setTimeout(() => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                }, 250);
            };
        </script>
    </body>
</html>
