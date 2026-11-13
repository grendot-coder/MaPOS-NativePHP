<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\GolonganIndex;
use App\Livewire\Admin\ProdukForm;
use App\Livewire\Admin\ProdukIndex;
use App\Livewire\Kasir\PosIndex as KasirPosIndex;
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
    });

Route::middleware(['auth', 'verified', 'role:kasir,admin'])
    ->prefix('kasir')
    ->name('kasir.')
    ->group(function () {
        Route::get('/pos', KasirPosIndex::class)->name('pos');
    });

require __DIR__.'/settings.php';
