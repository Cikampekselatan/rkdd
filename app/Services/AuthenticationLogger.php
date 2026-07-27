<?php

namespace App\Services;

use App\Models\AuthenticationLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthenticationLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(
        Request $request,
        string $event,
        ?User $user = null,
        ?string $email = null,
        ?string $providerUserId = null,
        array $context = [],
    ): ?AuthenticationLog {
        try {
            return AuthenticationLog::query()->create([
                'user_id' => $user?->id,
                'provider' => 'google',
                'provider_user_id' => $providerUserId,
                'email' => $email,
                'event' => $event,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'context' => $context === [] ? null : $context,
            ]);
        } catch (QueryException $exception) {
            Log::warning('Audit autentikasi tidak dapat disimpan ke database.', [
                'provider' => 'google',
                'event' => $event,
                'email' => $email,
                'exception' => $exception::class,
            ]);

            return null;
        }
    }
}
