<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleStudentAuthController;
use App\Http\Controllers\Onboarding\RegistrationCodeController as OnboardingRegistrationCodeController;
use App\Http\Controllers\Onboarding\WizardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/auth/google/redirect', [GoogleStudentAuthController::class, 'redirect'])
        ->middleware('throttle:10,1')
        ->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleStudentAuthController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('google.callback');
});

Route::get('/onboarding/pending', [GoogleStudentAuthController::class, 'pending'])
    ->middleware('auth')
    ->name('student.onboarding.pending');

Route::middleware(['auth', 'onboarding'])->prefix('onboarding')->name('onboarding.')->group(function (): void {
    Route::get('/registration-code', [OnboardingRegistrationCodeController::class, 'show'])
        ->name('registration-code.show');
    Route::post('/registration-code', [OnboardingRegistrationCodeController::class, 'store'])
        ->middleware('throttle:registration-code')
        ->name('registration-code.store');
    Route::get('/registration-code/accepted', [OnboardingRegistrationCodeController::class, 'accepted'])
        ->middleware('registration-code.validated')
        ->name('registration-code.accepted');

    Route::middleware('registration-code.validated')->prefix('wizard')->name('wizard.')->group(function (): void {
        Route::get('/{step}', [WizardController::class, 'show'])->name('show');
        Route::put('/identity', [WizardController::class, 'updateIdentity'])->name('identity.update');
        Route::put('/guardian', [WizardController::class, 'updateGuardian'])->name('guardian.update');
        Route::put('/access', [WizardController::class, 'updateAccess'])->name('access.update');
        Route::put('/interests', [WizardController::class, 'updateInterests'])->name('interests.update');
        Route::post('/agreements', [WizardController::class, 'finalize'])->name('agreements.finalize');
    });
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
