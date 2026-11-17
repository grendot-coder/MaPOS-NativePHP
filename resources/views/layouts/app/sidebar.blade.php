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

            <flux:sidebar.group heading="Referensi" class="grid">
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
                <flux:sidebar.item icon="banknotes" :href="route('admin.pengeluaran.index')" :current="request()->routeIs('admin.pengeluaran.index')" wire:navigate>
                    Pengeluaran & Kasbon
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

            <flux:sidebar.group heading="Pengaturan" class="grid">
                <flux:sidebar.item icon="building-storefront" :href="route('admin.profil-toko')" :current="request()->routeIs('admin.profil-toko')" wire:navigate>
                    Profil Toko
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
                icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()" />

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
                        data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    <!-- Workstation Subtle Loading Status (Non-overkill, Clean POS indicator) -->
    <div wire:loading.delay.200ms class="fixed top-3 right-4 z-50 pointer-events-none transition-opacity duration-150">
        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded bg-zinc-900/90 dark:bg-zinc-100/90 text-zinc-100 dark:text-zinc-900 text-[11px] font-mono font-medium shadow-sm border border-zinc-700/50 dark:border-zinc-300/50">
            <svg class="animate-spin size-3" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>Memproses...</span>
        </div>
    </div>

    @persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
    @endpersist

    @fluxScripts
    <script src="{{ asset('js/receipt-printer.js') }}?v={{ file_exists(public_path('js/receipt-printer.js')) ? filemtime(public_path('js/receipt-printer.js')) : time() }}"></script>
</body>

</html>