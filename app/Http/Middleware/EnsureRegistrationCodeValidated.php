<?php

namespace App\Http\Middleware;

use App\Actions\RegistrationCodes\ValidateRegistrationCode;
use App\Exceptions\RegistrationCodeRejected;
use App\Models\RegistrationCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationCodeValidated
{
    public function __construct(private readonly ValidateRegistrationCode $validateRegistrationCode) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $state = $request->session()->get('onboarding.registration_code');
        $registrationCode = is_array($state)
            ? RegistrationCode::query()->find($state['registration_code_id'] ?? null)
            : null;
        $validState = is_array($state)
            && ($state['user_id'] ?? null) === $request->user()?->id
            && filled($state['validated_at'] ?? null)
            && $registrationCode !== null;

        if ($validState) {
            try {
                $this->validateRegistrationCode->ensureAvailable($registrationCode);
            } catch (RegistrationCodeRejected) {
                $validState = false;
            }
        }

        if (! $validState) {
            $request->session()->forget('onboarding.registration_code');

            return redirect()
                ->route('onboarding.registration-code.show')
                ->withErrors(['code' => 'Validasi kode pendaftaran sudah tidak berlaku. Silakan masukkan kode kembali.']);
        }

        return $next($request);
    }
}
