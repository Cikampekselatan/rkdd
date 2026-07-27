<?php

namespace App\Actions\Onboarding;

use App\Actions\RegistrationCodes\ValidateRegistrationCode;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Exceptions\OnboardingFinalizationRejected;
use App\Models\ClassStudent;
use App\Models\RegistrationCode;
use App\Models\Role;
use App\Models\StudentOnboardingResponse;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinalizeStudentOnboarding
{
    public function __construct(private readonly ValidateRegistrationCode $validateRegistrationCode) {}

    /**
     * @param  array<string, mixed>  $sessionState
     * @param  array<string, mixed>  $agreements
     */
    public function execute(User $user, array $sessionState, array $agreements): User
    {
        return DB::transaction(function () use ($agreements, $sessionState, $user): User {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $response = StudentOnboardingResponse::query()->lockForUpdate()->where('user_id', $lockedUser->id)->first();

            if ($response?->completed_at !== null) {
                return $lockedUser->fresh('roles');
            }

            if (($sessionState['user_id'] ?? null) !== $lockedUser->id) {
                throw new OnboardingFinalizationRejected('State kode pendaftaran tidak sesuai dengan akun.');
            }

            foreach (['agree_rules', 'agree_privacy', 'agree_ai_policy', 'agree_publication_policy'] as $agreement) {
                if (! filter_var($agreements[$agreement] ?? false, FILTER_VALIDATE_BOOL)) {
                    throw new OnboardingFinalizationRejected('Semua persetujuan wajib diberikan.');
                }
            }

            $profile = StudentProfile::query()->lockForUpdate()->where('user_id', $lockedUser->id)->first();

            if ($profile === null || $response === null || $response->current_step < 5) {
                throw new OnboardingFinalizationRejected('Data onboarding belum lengkap.');
            }

            $this->ensureDraftComplete($profile, $response);

            $registrationCode = RegistrationCode::query()
                ->lockForUpdate()
                ->find($sessionState['registration_code_id'] ?? null);

            if ($registrationCode === null) {
                throw new OnboardingFinalizationRejected('Kode pendaftaran tidak ditemukan.');
            }

            $this->validateRegistrationCode->ensureAvailable($registrationCode);

            if ($registrationCode->class_id !== null && $profile->class_id !== $registrationCode->class_id) {
                throw new OnboardingFinalizationRejected('Kelompok peserta tidak sesuai dengan kode pendaftaran.');
            }

            if ($registrationCode->program_batch_id !== null && $profile->schoolClass?->program_batch_id !== $registrationCode->program_batch_id) {
                throw new OnboardingFinalizationRejected('Kelompok peserta tidak sesuai dengan program kode pendaftaran.');
            }

            $now = now();

            $response->update([
                'registration_code_id' => $registrationCode->id,
                'agreed_rules_at' => $now,
                'agreed_privacy_at' => $now,
                'agreed_ai_policy_at' => $now,
                'agreed_publication_policy_at' => $now,
                'completed_at' => $now,
            ]);

            $profile->update([
                'program_batch_id' => $registrationCode->program_batch_id,
                'joined_at' => $profile->joined_at ?? $now,
                'membership_status' => StudentMembershipStatus::Active,
            ]);

            ClassStudent::query()->firstOrCreate(
                [
                    'academic_year_id' => $registrationCode->academic_year_id,
                    'class_id' => $profile->class_id,
                    'user_id' => $lockedUser->id,
                ],
                [
                    'program_batch_id' => $registrationCode->program_batch_id,
                    'joined_at' => $now,
                    'status' => 'active',
                ],
            );

            $studentRole = Role::query()->where('slug', RoleSlug::Student->value)->firstOrFail();
            $lockedUser->roles()->syncWithoutDetaching($studentRole);
            $lockedUser->update(['status' => UserStatus::Active]);

            $registrationCode->increment('used_count');

            return $lockedUser->fresh('roles');
        }, 3);
    }

    private function ensureDraftComplete(StudentProfile $profile, StudentOnboardingResponse $response): void
    {
        foreach (['student_number', 'gender', 'birth_date', 'grade_level', 'school_class_name', 'class_id', 'parent_name', 'parent_phone', 'guardian_relationship'] as $field) {
            if (blank($profile->{$field})) {
                throw new OnboardingFinalizationRejected('Profil peserta belum lengkap.');
            }
        }

        if (
            empty($response->device_access)
            || blank($response->internet_access)
            || empty($response->interests)
            || blank($response->expectation)
            || blank($response->learning_targets)
        ) {
            throw new OnboardingFinalizationRejected('Respons onboarding belum lengkap.');
        }
    }
}
