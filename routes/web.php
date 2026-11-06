<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'login')->name('login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('items', ItemController::class)->except(['create', 'edit', 'show']);

    Route::get('stock-in', [StockInController::class, 'index'])->name('stock-in.index');
    Route::post('stock-in', [StockInController::class, 'store'])->name('stock-in.store');

    Route::get('stock-out', [StockOutController::class, 'index'])->name('stock-out.index');
    Route::post('stock-out', [StockOutController::class, 'store'])->name('stock-out.store');

    Route::get('reports/movements', [ReportController::class, 'movements'])->name('reports.movements');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');

    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    });
});

require __DIR__.'/settings.php';
