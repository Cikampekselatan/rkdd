<?php

use App\Http\Controllers\Coach\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:coach'])->prefix('coach')->name('coach.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
});
