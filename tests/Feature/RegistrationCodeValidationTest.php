<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\RegistrationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationCodeValidationTest extends TestCase
{
    use RefreshDatabase;

    private const PLAIN_CODE = 'SKUAD-ABCDE-FGHJK-MNPQR-STUVW';

    public function test_valid_code_is_saved_to_the_current_users_onboarding_session(): void
    {
        $user = $this->onboardingUser();
        $registrationCode = $this->registrationCode();

        $this->actingAs($user)
            ->post(route('onboarding.registration-code.store'), [
                'code' => mb_strtolower(self::PLAIN_CODE),
            ])
            ->assertRedirect(route('onboarding.registration-code.accepted'))
            ->assertSessionHas('onboarding.registration_code.user_id', $user->id)
            ->assertSessionHas('onboarding.registration_code.registration_code_id', $registrationCode->id);

        $this->assertSame(0, $registrationCode->fresh()->used_count);

        $this->actingAs($user)
            ->get(route('onboarding.registration-code.accepted'))
            ->assertOk()
            ->assertSee('Kode terverifikasi');
    }

    public function test_wrong_code_is_rejected(): void
    {
        $user = $this->onboardingUser();

        $this->actingAs($user)
            ->post(route('onboarding.registration-code.store'), ['code' => 'SKUAD-KODE-SALAH'])
            ->assertSessionHasErrors('code');
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = $this->onboardingUser();
        $this->registrationCode(['expires_at' => now()->subMinute()]);

        $this->actingAs($user)
            ->post(route('onboarding.registration-code.store'), ['code' => self::PLAIN_CODE])
            ->assertSessionHasErrors(['code' => 'Kode pendaftaran sudah kedaluwarsa.']);
    }

    public function test_inactive_code_is_rejected(): void
    {
        $user = $this->onboardingUser();
        $this->registrationCode(['is_active' => false]);

        $this->actingAs($user)
            ->post(route('onboarding.registration-code.store'), ['code' => self::PLAIN_CODE])
            ->assertSessionHasErrors(['code' => 'Kode pendaftaran sedang tidak aktif.']);
    }

    public function test_code_that_has_not_started_is_rejected(): void
    {
        $user = $this->onboardingUser();
        $this->registrationCode(['starts_at' => now()->addHour()]);

        $this->actingAs($user)
            ->post(route('onboarding.registration-code.store'), ['code' => self::PLAIN_CODE])
            ->assertSessionHasErrors(['code' => 'Kode pendaftaran belum dapat digunakan.']);
    }

    public function test_code_at_usage_limit_is_rejected(): void
    {
        $user = $this->onboardingUser();
        $this->registrationCode(['max_uses' => 10, 'used_count' => 10]);

        $this->actingAs($user)
            ->post(route('onboarding.registration-code.store'), ['code' => self::PLAIN_CODE])
            ->assertSessionHasErrors(['code' => 'Batas penggunaan kode pendaftaran sudah tercapai.']);
    }

    public function test_registration_code_validation_is_rate_limited(): void
    {
        $user = $this->onboardingUser();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user)->post(route('onboarding.registration-code.store'), [
                'code' => 'KODE-SALAH-'.$attempt,
            ]);
        }

        $this->actingAs($user)
            ->post(route('onboarding.registration-code.store'), ['code' => 'KODE-SALAH-LAGI'])
            ->assertTooManyRequests();
    }

    public function test_guest_and_active_student_cannot_open_code_validation(): void
    {
        $this->get(route('onboarding.registration-code.show'))->assertRedirect(route('login'));

        $activeStudent = User::factory()->withRole(RoleSlug::Student)->create();

        $this->actingAs($activeStudent)
            ->get(route('onboarding.registration-code.show'))
            ->assertForbidden();
    }

    public function test_session_state_cannot_be_reused_by_another_user(): void
    {
        $firstUser = $this->onboardingUser();
        $secondUser = $this->onboardingUser();
        $registrationCode = $this->registrationCode();

        $this->actingAs($secondUser)
            ->withSession([
                'onboarding.registration_code' => [
                    'user_id' => $firstUser->id,
                    'registration_code_id' => $registrationCode->id,
                    'validated_at' => now()->toIso8601String(),
                ],
            ])
            ->get(route('onboarding.registration-code.accepted'))
            ->assertRedirect(route('onboarding.registration-code.show'))
            ->assertSessionHasErrors('code');
    }

    private function onboardingUser(): User
    {
        return User::factory()->create([
            'status' => UserStatus::Onboarding,
            'password' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function registrationCode(array $attributes = []): RegistrationCode
    {
        return RegistrationCode::factory()
            ->forPlainText(self::PLAIN_CODE)
            ->create($attributes);
    }
}
