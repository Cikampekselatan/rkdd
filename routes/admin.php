<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegistrationCodeController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('role:super-admin,admin')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('registration-codes', RegistrationCodeController::class)->except('show');
        Route::resource('academic-years', AcademicYearController::class)->except('show');
        Route::resource('classes', SchoolClassController::class)
            ->parameters(['classes' => 'school_class'])
            ->except('show');
        Route::resource('staff', StaffController::class)->except('show');
        Route::patch('students/{student}/suspend', [StudentController::class, 'suspend'])->name('students.suspend');
        Route::patch('students/{student}/deactivate', [StudentController::class, 'deactivate'])->name('students.deactivate');
        Route::patch('students/{student}/activate', [StudentController::class, 'activate'])->name('students.activate');
        Route::patch('students/{student}/reset-onboarding', [StudentController::class, 'resetOnboarding'])->withTrashed()->name('students.reset-onboarding');
        Route::delete('students/{student}/purge-test', [StudentController::class, 'purgeTest'])->withTrashed()->name('students.purge-test');
        Route::delete('students/{student}', [StudentController::class, 'destroy'])->withTrashed()->name('students.destroy');
    });

    Route::middleware('role:super-admin,admin,teacher,coach')->group(function (): void {
        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/{student}', [StudentController::class, 'show'])->withTrashed()->name('students.show');
    });
});
