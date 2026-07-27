<?php

use App\Http\Controllers\Principal\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:principal'])->prefix('principal')->name('principal.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
});
