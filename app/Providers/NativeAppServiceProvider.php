<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Dijalankan otomatis saat aplikasi desktop NativePHP pertama kali di-booting.
     */
    public function boot(): void
    {
        // 1. Sembunyikan menu bar bawaan Electron (File, Edit, View, dsb) agar border garis putih hilang
        Menu::create();

        // 2. Buka jendela utama aplikasi desktop
        Window::open()
            ->title('MaPOS - Manajemen POS v1.1.2')
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(700)
            ->hideMenu()
            ->rememberState()
            ->showDevTools(false);

        // 3. Pastikan migrasi terbaru berjalan & auto-seed data awal jika database desktop masih kosong
        try {
            if (config('nativephp-internal.running')) {
                Artisan::call('migrate', ['--force' => true]);
            }

            if (User::count() === 0) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            // Lanjutkan jika tabel sedang dalam tahap migrasi awal atau offline
        }
    }

    /**
     * Pengaturan direktif php.ini khusus desktop (jika diperlukan).
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'display_errors' => app()->environment('local') ? '1' : '0',
            'error_reporting' => app()->environment('local') ? 'E_ALL' : 'E_NONE',
            'opcache.enable' => '1',
            'opcache.enable_cli' => '1',
        ];
    }
}
