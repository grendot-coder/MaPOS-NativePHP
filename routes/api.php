<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MaPOS API Routes
|--------------------------------------------------------------------------
|
| API routes used for synchronization between Desktop POS terminals
| and the central Web-hosted application.
|
*/

Route::middleware('sync.auth')->prefix('sync')->group(function () {
    Route::get('/ping', [SyncController::class, 'ping'])->name('api.sync.ping');
    Route::post('/sales', [SyncController::class, 'syncSales'])->name('api.sync.sales');
    Route::post('/batch', [SyncController::class, 'syncBatch'])->name('api.sync.batch');
    Route::get('/catalog', [SyncController::class, 'getCatalog'])->name('api.sync.catalog');
});
