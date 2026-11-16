<?php

use App\Livewire\Admin\BarcodeGenerator;
use App\Livewire\Admin\BarcodePrintLabel;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\GolonganIndex;
use App\Livewire\Admin\LaporanPenjualan;
use App\Livewire\Admin\LaporanStok;
use App\Livewire\Admin\ProdukForm;
use App\Livewire\Admin\ProdukIndex;
use App\Livewire\Admin\StokPenyesuaian;
use App\Livewire\Admin\StokRiwayat;
use App\Livewire\Kasir\PosIndex as KasirPosIndex;
use App\Livewire\Kasir\RiwayatPenjualan;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\PengeluaranIndex;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('kasir.pos');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::get('/golongan', GolonganIndex::class)->name('golongan.index');
        Route::get('/produk', ProdukIndex::class)->name('produk.index');
        Route::get('/produk/create', ProdukForm::class)->name('produk.create');
        Route::get('/produk/{id}/edit', ProdukForm::class)->name('produk.edit');
        Route::get('/barcode', BarcodeGenerator::class)->name('barcode.index');
        Route::get('/barcode/print-label', BarcodePrintLabel::class)->name('barcode.print-label');
        Route::get('/stok/penyesuaian', StokPenyesuaian::class)->name('stok.penyesuaian');
        Route::get('/stok/riwayat', StokRiwayat::class)->name('stok.riwayat');
        Route::get('/pengeluaran', PengeluaranIndex::class)->name('pengeluaran.index');
        Route::get('/laporan/penjualan', LaporanPenjualan::class)->name('laporan.penjualan');
        Route::get('/laporan/stok', LaporanStok::class)->name('laporan.stok');
    });

Route::middleware(['auth', 'verified', 'role:kasir,admin'])
    ->prefix('kasir')
    ->name('kasir.')
    ->group(function () {
        Route::get('/pos', KasirPosIndex::class)->name('pos');
        Route::get('/riwayat', RiwayatPenjualan::class)->name('riwayat');
    });

require __DIR__ . '/settings.php';
