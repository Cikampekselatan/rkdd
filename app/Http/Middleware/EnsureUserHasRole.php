<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use Closure;
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

        abort_unless($user !== null && $user->hasAnyRole($roles), 403);
        abort_if($user->hasRole(RoleSlug::Student) && ! $user->isStaff() && $user->status !== UserStatus::Active, 403);

        return $next($request);
    }
}
