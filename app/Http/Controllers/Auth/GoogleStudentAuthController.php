<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateGoogleStudent;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Exceptions\DisallowedGoogleEmailDomain;
use App\Exceptions\GoogleAccountRejected;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationLogger;
use App\Support\AllowedStudentEmailDomains;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GoogleStudentAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()->route('login')->withErrors([
                'google' => 'Konfigurasi Google OAuth belum lengkap. Mohon hubungi admin untuk mengisi GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_REDIRECT_URI.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(
        Request $request,
        AuthenticateGoogleStudent $authenticateGoogleStudent,
        AllowedStudentEmailDomains $allowedDomains,
        AuthenticationLogger $logger,
    ): Response {
        try {
            $result = $authenticateGoogleStudent->execute(
                Socialite::driver('google')->user(),
                $request,
            );
        } catch (DisallowedGoogleEmailDomain $exception) {
            return response()->view('auth.google-domain-error', [
                'email' => $exception->email,
                'domain' => $exception->domain,
                'allowedDomains' => $allowedDomains->all(),
            ], 403);
        } catch (GoogleAccountRejected $exception) {
            return redirect()->route('login')->withErrors([
                'google' => $exception->getMessage(),
            ]);
        } catch (InvalidStateException $exception) {
            $logger->log($request, 'rejected_invalid_state');

            return redirect()->route('login')->withErrors([
                'google' => 'Sesi Google tidak valid atau sudah kedaluwarsa. Silakan coba kembali.',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $logger->log($request, 'provider_error', context: [
                'exception' => $exception::class,
            ]);

            return redirect()->route('login')->withErrors([
                'google' => 'Google tidak dapat dihubungi saat ini. Silakan coba kembali.',
            ]);
        }

        Auth::login($result->user);
        $request->session()->regenerate();

        if ($result->user->status === UserStatus::Onboarding || ! $result->user->hasRole(RoleSlug::Student)) {
            return redirect()->route('student.onboarding.pending');
        }

        return redirect()->route('student.dashboard');
    }

    public function pending(Request $request): View
    {
        abort_unless(
            $request->user()?->status === UserStatus::Onboarding && ! $request->user()->isStaff(),
            403,
        );

        return view('auth.onboarding-pending');
    }

    private function hasGoogleConfiguration(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
