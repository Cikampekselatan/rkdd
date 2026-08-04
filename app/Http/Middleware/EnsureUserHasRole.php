<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (
            $user !== null
            && ! $user->isStaff()
            && $user->status === UserStatus::Onboarding
            && in_array(RoleSlug::Student->value, $roles, true)
        ) {
            return $this->redirectOnboardingStudent($request);
        }

        abort_unless($user !== null && $user->hasAnyRole($roles), 403);
        abort_if($user->hasRole(RoleSlug::Student) && ! $user->isStaff() && $user->status !== UserStatus::Active, 403);

        return $next($request);
    }

    private function redirectOnboardingStudent(Request $request): RedirectResponse
    {
        $state = $request->session()->get('onboarding.registration_code');
        $hasValidatedCode = is_array($state)
            && ($state['user_id'] ?? null) === $request->user()?->id
            && filled($state['registration_code_id'] ?? null)
            && filled($state['validated_at'] ?? null);

        return redirect()->route(
            $hasValidatedCode
                ? 'onboarding.registration-code.accepted'
                : 'onboarding.registration-code.show',
        );
    }
}
