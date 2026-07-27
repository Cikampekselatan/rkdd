<?php

namespace App\Actions\Auth;

use App\Data\GoogleAuthenticationResult;
use App\Enums\UserStatus;
use App\Exceptions\DisallowedGoogleEmailDomain;
use App\Exceptions\GoogleAccountRejected;
use App\Models\User;
use App\Services\AuthenticationLogger;
use App\Support\AllowedStudentEmailDomains;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthenticateGoogleStudent
{
    public function __construct(
        private readonly AllowedStudentEmailDomains $allowedDomains,
        private readonly AuthenticationLogger $logger,
    ) {}

    public function execute(SocialiteUser $googleUser, Request $request): GoogleAuthenticationResult
    {
        $providerId = trim((string) $googleUser->getId());
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $name = trim((string) $googleUser->getName());
        $domain = $this->allowedDomains->domainFrom($email);

        if ($providerId === '' || $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->logger->log($request, 'rejected_invalid_profile', email: $email ?: null, providerUserId: $providerId ?: null);

            throw new GoogleAccountRejected('invalid_profile', 'Profil Google tidak menyediakan identitas yang valid.');
        }

        if (! $this->allowedDomains->allows($email)) {
            $this->logger->log(
                $request,
                'rejected_domain',
                email: $email,
                providerUserId: $providerId,
                context: ['domain' => $domain],
            );

            throw new DisallowedGoogleEmailDomain($email, $domain);
        }

        $user = User::query()
            ->with('roles')
            ->where('google_id', $providerId)
            ->first();

        $user ??= User::query()->with('roles')->where('email', $email)->first();

        if ($user?->isStaff()) {
            $this->logger->log($request, 'rejected_staff_account', $user, $email, $providerId);

            throw new GoogleAccountRejected('staff_account');
        }

        if ($user?->google_id !== null && $user->google_id !== $providerId) {
            $this->logger->log($request, 'rejected_identity_conflict', $user, $email, $providerId);

            throw new GoogleAccountRejected('identity_conflict');
        }

        if ($user !== null && in_array($user->status, [
            UserStatus::Suspended,
            UserStatus::Inactive,
            UserStatus::Graduated,
            UserStatus::Archived,
        ], true)) {
            $this->logger->log($request, 'rejected_inactive_account', $user, $email, $providerId, [
                'status' => $user->status->value,
            ]);

            throw new GoogleAccountRejected('inactive_account', 'Akun siswa sedang tidak aktif. Hubungi pembina SKUAD.');
        }

        return DB::transaction(function () use ($email, $googleUser, $name, $providerId, $request, $user): GoogleAuthenticationResult {
            $created = $user === null;

            if ($created) {
                $user = User::query()->create([
                    'google_id' => $providerId,
                    'name' => $name !== '' ? $name : ($googleUser->getNickname() ?: 'Siswa SKUAD'),
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => null,
                    'status' => UserStatus::Onboarding,
                ]);
            } else {
                $attributes = [
                    'google_id' => $providerId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ];

                if ($user->roles->isEmpty() && $user->status === UserStatus::Active) {
                    $attributes['status'] = UserStatus::Onboarding;
                }

                $user->forceFill($attributes)->save();
            }

            $this->logger->log(
                $request,
                $created ? 'login_created' : 'login_success',
                $user,
                $email,
                $providerId,
                ['status' => $user->status->value],
            );

            return new GoogleAuthenticationResult($user, $created);
        });
    }
}
