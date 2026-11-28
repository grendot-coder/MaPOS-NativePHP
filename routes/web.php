<?php

use App\Livewire\Admin\BarcodeGenerator;
use App\Livewire\Admin\BarcodePrintLabel;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\DropshipperIndex;
use App\Livewire\Admin\GolonganIndex;
use App\Livewire\Admin\JabatanIndex;
use App\Livewire\Admin\KaryawanIndex;
use App\Livewire\Admin\LaporanPenjualan;
use App\Livewire\Admin\LaporanStok;
use App\Livewire\Admin\PelangganIndex;
use App\Livewire\Admin\PembelianForm;
use App\Livewire\Admin\PembelianIndex;
use App\Livewire\Admin\PengeluaranIndex;
use App\Livewire\Admin\PenggajianIndex;
use App\Livewire\Admin\PenggunaIndex;
use App\Livewire\Admin\ProdukForm;
use App\Livewire\Admin\ProdukIndex;
use App\Livewire\Admin\ProfilToko;
use App\Livewire\Admin\ReturIndex;
use App\Livewire\Admin\StokPenyesuaian;
use App\Livewire\Admin\StokRiwayat;
use App\Livewire\Admin\SyncIndex;
use App\Livewire\Kasir\PosIndex as KasirPosIndex;
use App\Livewire\Kasir\RiwayatPenjualan;
use Illuminate\Support\Facades\Route;

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
        Route::get('/pembelian', PembelianIndex::class)->name('pembelian.index');
        Route::get('/pembelian/create', PembelianForm::class)->name('pembelian.create');
        Route::get('/pengeluaran', PengeluaranIndex::class)->name('pengeluaran.index');
        Route::get('/pelanggan', PelangganIndex::class)->name('pelanggan.index');
        Route::get('/dropshipper', DropshipperIndex::class)->name('dropshipper.index');
        Route::get('/retur', ReturIndex::class)->name('retur.index');
        Route::get('/jabatan', JabatanIndex::class)->name('jabatan.index');
        Route::get('/karyawan', KaryawanIndex::class)->name('karyawan.index');
        Route::get('/penggajian', PenggajianIndex::class)->name('penggajian.index');
        Route::get('/laporan/penjualan', LaporanPenjualan::class)->name('laporan.penjualan');
        Route::get('/laporan/stok', LaporanStok::class)->name('laporan.stok');
        Route::get('/profil-toko', ProfilToko::class)->name('profil-toko');
        Route::get('/pengguna', PenggunaIndex::class)->name('pengguna.index');
        Route::get('/sinkronisasi', SyncIndex::class)->name('sinkronisasi.index');
    });

Route::middleware(['auth', 'verified', 'role:kasir,admin'])
    ->prefix('kasir')
    ->name('kasir.')
    ->group(function () {
        Route::get('/pos', KasirPosIndex::class)->name('pos');
        Route::get('/riwayat', RiwayatPenjualan::class)->name('riwayat');
    });

require __DIR__.'/settings.php';
