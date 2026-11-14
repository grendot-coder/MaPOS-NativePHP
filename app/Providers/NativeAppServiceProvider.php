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
            ->title('MaPOS - Manajemen Point of Sale v1.0')
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
            'display_errors' => '1',
            'error_reporting' => 'E_ALL',
        ];
    }
}
