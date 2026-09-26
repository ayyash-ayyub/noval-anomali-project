<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FailedJobController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\MikrotikController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\VoucherPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('mikrotiks')->name('mikrotiks.')->group(function () {
        Route::get('/', [MikrotikController::class, 'index'])->name('index');
        Route::get('/create', [MikrotikController::class, 'create'])->name('create');
        Route::post('/', [MikrotikController::class, 'store'])->name('store');
        Route::get('/{mikrotik}', [MikrotikController::class, 'show'])->name('show');
        Route::get('/{mikrotik}/edit', [MikrotikController::class, 'edit'])->name('edit');
        Route::put('/{mikrotik}', [MikrotikController::class, 'update'])->name('update');
        Route::delete('/{mikrotik}', [MikrotikController::class, 'destroy'])->name('destroy');
        Route::post('/{mikrotik}/test', [MikrotikController::class, 'testConnection'])->name('test')->middleware('throttle:mikrotik-test');
        Route::post('/{mikrotik}/router-info', [MikrotikController::class, 'routerInfo'])->name('router-info')->middleware('throttle:mikrotik-test');
        Route::get('/{mikrotik}/sync', [MikrotikController::class, 'sync'])->name('sync')->middleware('throttle:mikrotik-test');
    });

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::prefix('hotspot')->name('hotspot.')->group(function () {
        Route::get('/profiles', [HotspotController::class, 'profiles'])->name('profiles');
        Route::get('/users', [HotspotController::class, 'users'])->name('users');
        Route::get('/active', [HotspotController::class, 'active'])->name('active');
    });

    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/generate', [VoucherController::class, 'create'])->name('generate');
        Route::post('/generate', [VoucherController::class, 'store'])->name('store')->middleware('throttle:voucher-generate');

        Route::get('/batches', [BatchController::class, 'index'])->name('batches');
        Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
        Route::get('/batches/{batch}/progress', [BatchController::class, 'progress'])->name('batches.progress');

        // Must stay before the /{voucher} wildcard route so "print"
        // isn't matched as an id.
        Route::get('/print', [VoucherPrintController::class, 'index'])->name('print');
        Route::post('/print', [VoucherPrintController::class, 'store'])->name('print.store')->middleware('throttle:pdf-export');
        Route::get('/print/{export}', [VoucherPrintController::class, 'show'])->name('print.show');
        Route::get('/print/{export}/progress', [VoucherPrintController::class, 'progress'])->name('print.progress');
        Route::get('/print/{export}/download', [VoucherPrintController::class, 'download'])->name('print.download');

        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::get('/{voucher}', [VoucherController::class, 'show'])->name('show');
        Route::post('/{voucher}/disable', [VoucherController::class, 'disable'])->name('disable')->middleware('throttle:mikrotik-test');
        Route::post('/{voucher}/retry', [VoucherController::class, 'retry'])->name('retry')->middleware('throttle:mikrotik-test');
    });

    Route::get('/failed-jobs', [FailedJobController::class, 'index'])->name('failed-jobs.index');
    Route::post('/failed-jobs/{id}/retry', [FailedJobController::class, 'retry'])->name('failed-jobs.retry');
    Route::delete('/failed-jobs/{id}', [FailedJobController::class, 'destroy'])->name('failed-jobs.destroy');

    Route::view('/reports', 'coming-soon', [
        'module' => 'Reports',
        'description' => 'Laporan penggunaan voucher dan MikroTik.',
    ])->name('reports.index');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});

require __DIR__.'/auth.php';
