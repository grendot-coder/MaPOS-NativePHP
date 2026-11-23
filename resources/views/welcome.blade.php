<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MaPOS - Sistem Kasir & Manajemen Retail POS</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-100 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 font-sans flex flex-col justify-between p-6">

    <!-- Header Terminal Minimalis -->
    <header class="w-full max-w-5xl mx-auto flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center gap-2.5">
            <div class="size-8 rounded bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 flex items-center justify-center p-1.5 shadow-sm">
                <x-app-logo-icon class="size-5" />
            </div>
            <div>
                <div class="font-bold text-sm tracking-wide leading-tight">MaPOS WORKSTATION</div>
                <div class="text-[11px] text-zinc-500 font-mono">Retail Point of Sale v1.1.2</div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @auth
            <a
                href="{{ route('dashboard') }}"
                class="px-3.5 py-1.5 rounded text-xs font-bold text-white bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition"
                wire:navigate>
                Buka Terminal &rarr;
            </a>
            @else
            <a
                href="{{ route('login') }}"
                class="px-3.5 py-1.5 rounded text-xs font-bold text-white bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition"
                wire:navigate>
                Masuk Kasir / Admin
            </a>
            @endauth
        </div>
    </header>

    <!-- Main Content: Entry Terminal POS -->
    <main class="w-full max-w-3xl mx-auto my-auto py-12 space-y-6">
        <div class="text-center space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded bg-zinc-200 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-mono border border-zinc-300 dark:border-zinc-700">
                <span>STATION ID: #POS-TERMINAL-01</span>
                <span>&bull;</span>
                <span>{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>

            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                Sistem Kasir & Manajemen Toko
            </h1>

            <p class="text-sm text-zinc-600 dark:text-zinc-400 max-w-xl mx-auto leading-relaxed">
                Aplikasi Point of Sale workstation terintegrasi untuk kasir cepat, pemindaian barcode USB & kamera smartphone, manajemen stok ritel, dan pembukuan kas.
            </p>
        </div>

        <!-- Tombol Aksi Masuk Kasir / Terminal -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            @auth
            <a
                href="{{ route('kasir.pos') }}"
                class="w-full sm:w-auto px-6 py-3 rounded text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition flex items-center justify-center gap-2 shadow-sm font-mono"
                wire:navigate>
                <flux:icon name="shopping-cart" class="size-4" />
                <span>Buka Layar Kasir (POS)</span>
            </a>
            <a
                href="{{ route('admin.dashboard') }}"
                class="w-full sm:w-auto px-5 py-3 rounded text-sm font-semibold text-zinc-800 dark:text-zinc-200 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 transition flex items-center justify-center gap-2"
                wire:navigate>
                <flux:icon name="home" class="size-4 text-zinc-500" />
                <span>Dashboard Administrator</span>
            </a>
            @else
            <a
                href="{{ route('login') }}"
                class="w-full sm:w-auto px-8 py-3 rounded text-sm font-bold text-white bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition flex items-center justify-center gap-2 shadow-sm"
                wire:navigate>
                <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                <span>Masuk ke Akun Kasir / Admin</span>
            </a>
            @endauth
        </div>

        <!-- Grid Status Spesifikasi Workstation -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-6 border-t border-zinc-200 dark:border-zinc-800 text-xs font-mono">
            <div class="p-3 rounded bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 space-y-1">
                <div class="text-[10px] uppercase font-bold text-zinc-500">Mode Operasional</div>
                <div class="font-bold text-zinc-800 dark:text-zinc-200">Desktop & Browser</div>
                <div class="text-[10px] text-zinc-400">Offline Standalone Ready</div>
            </div>

            <div class="p-3 rounded bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 space-y-1">
                <div class="text-[10px] uppercase font-bold text-zinc-500">Perangkat Kasir</div>
                <div class="font-bold text-zinc-800 dark:text-zinc-200">Barcode & Thermal 58/80</div>
                <div class="text-[10px] text-zinc-400">Dukungan Kamera HP & USB</div>
            </div>

            <div class="p-3 rounded bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 space-y-1">
                <div class="text-[10px] uppercase font-bold text-zinc-500">Keamanan Kas</div>
                <div class="font-bold text-zinc-800 dark:text-zinc-200">Manajemen Shift Laci</div>
                <div class="text-[10px] text-zinc-400">Rekonsiliasi Fisik & Sistem</div>
            </div>
        </div>
    </main>

    <!-- Footer Copyright Workstation -->
    <footer class="w-full max-w-5xl mx-auto flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-800 text-xs text-zinc-500 font-mono">
        <div>MaPOS &bull; Point of Sale & Retail Management</div>
        <div>{{ config('app.name') }} &copy; {{ date('Y') }}</div>
    </footer>

</body>

</html>