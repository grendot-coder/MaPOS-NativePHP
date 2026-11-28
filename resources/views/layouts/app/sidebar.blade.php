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
                    Kategori Produk
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
                <flux:sidebar.item icon="arrow-down-tray" :href="route('admin.pembelian.index')" :current="request()->routeIs('admin.pembelian.*')" wire:navigate>
                    Pembelian (Restock)
                </flux:sidebar.item>
                <flux:sidebar.item icon="banknotes" :href="route('admin.pengeluaran.index')" :current="request()->routeIs('admin.pengeluaran.index')" wire:navigate>
                    Pengeluaran & Kasbon
                </flux:sidebar.item>
                <flux:sidebar.item icon="users" :href="route('admin.pelanggan.index')" :current="request()->routeIs('admin.pelanggan.*')" wire:navigate>
                    Pelanggan & Piutang
                </flux:sidebar.item>
                <flux:sidebar.item icon="truck" :href="route('admin.dropshipper.index')" :current="request()->routeIs('admin.dropshipper.*')" wire:navigate>
                    Mitra Dropshipper
                </flux:sidebar.item>
                <flux:sidebar.item icon="arrow-uturn-left" :href="route('admin.retur.index')" :current="request()->routeIs('admin.retur.*')" wire:navigate>
                    Retur Barang
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group heading="Kepegawaian" class="grid">
                <flux:sidebar.item icon="briefcase" :href="route('admin.jabatan.index')" :current="request()->routeIs('admin.jabatan.*')" wire:navigate>
                    Master Jabatan
                </flux:sidebar.item>
                <flux:sidebar.item icon="identification" :href="route('admin.karyawan.index')" :current="request()->routeIs('admin.karyawan.*')" wire:navigate>
                    Data Karyawan
                </flux:sidebar.item>
                <flux:sidebar.item icon="wallet" :href="route('admin.penggajian.index')" :current="request()->routeIs('admin.penggajian.*')" wire:navigate>
                    Penggajian (Payroll)
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
                <flux:sidebar.item icon="user-group" :href="route('admin.pengguna.index')" :current="request()->routeIs('admin.pengguna.*')" wire:navigate>
                    Akun Pengguna
                </flux:sidebar.item>
                <flux:sidebar.item icon="building-storefront" :href="route('admin.profil-toko')" :current="request()->routeIs('admin.profil-toko')" wire:navigate>
                    Profil Toko
                </flux:sidebar.item>
                <flux:sidebar.item icon="arrow-path" :href="route('admin.sinkronisasi.index')" :current="request()->routeIs('admin.sinkronisasi.*')" wire:navigate>
                    Sinkronisasi & Backup DB
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
            <div class="font-semibold text-zinc-500">MaPOS v1.1.2</div>
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

    @persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
    @endpersist

    @fluxScripts
    <script src="{{ asset('js/receipt-printer.js') }}?v={{ file_exists(public_path('js/receipt-printer.js')) ? filemtime(public_path('js/receipt-printer.js')) : time() }}"></script>
</body>

</html>