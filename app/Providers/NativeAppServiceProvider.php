<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Dijalankan otomatis saat aplikasi desktop NativePHP pertama kali di-booting.
     */
    public function boot(): void
    {
        // 1. Buka jendela utama aplikasi desktop
        Window::open()
            ->title('MaPOS - Manajemen POS v1.1.0')
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(700)
            ->rememberState()
            ->showDevTools(app()->environment('local'));

        // 2. Auto-seed data awal jika database lokal desktop masih kosong
        try {
            if (User::count() === 0) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            // Lanjutkan jika tabel sedang dalam tahap migrasi awal
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
            'opcache.memory_consumption' => '128',
            'opcache.max_accelerated_files' => '10000',
        ];
    }
}
