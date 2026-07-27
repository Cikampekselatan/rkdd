<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureRegistrationCodeValidated;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsOnboarding;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            foreach ([
                'auth',
                'super-admin',
                'admin',
                'teacher',
                'coach',
                'student',
                'principal',
            ] as $routeFile) {
                Route::middleware('web')->group(base_path("routes/{$routeFile}.php"));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);

        $middleware->alias([
            'onboarding' => EnsureUserIsOnboarding::class,
            'registration-code.validated' => EnsureRegistrationCodeValidated::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
