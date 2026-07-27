<?php

namespace Tests\Feature;

use App\Actions\Onboarding\FinalizeStudentOnboarding;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Exceptions\OnboardingFinalizationRejected;
use App\Exceptions\RegistrationCodeRejected;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\StudentOnboardingResponse;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFinalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_finalization_action_is_idempotent(): void
    {
        [$user, $code] = $this->completeDraft();
        $action = app(FinalizeStudentOnboarding::class);

        $action->execute($user, $this->state($user, $code), $this->agreements());
        $action->execute($user, [], []);

        $this->assertSame(1, $code->fresh()->used_count);
        $this->assertDatabaseCount('class_students', 1);
        $this->assertDatabaseCount('role_user', 1);
        $this->assertTrue($user->fresh()->hasRole(RoleSlug::Student));
    }

    public function test_finalization_rejects_state_from_another_user_without_partial_writes(): void
    {
        [$user, $code] = $this->completeDraft();
        $otherUser = User::factory()->create();

        try {
            app(FinalizeStudentOnboarding::class)->execute(
                $user,
                $this->state($otherUser, $code),
                $this->agreements(),
            );
            $this->fail('Finalization should reject cross-user state.');
        } catch (OnboardingFinalizationRejected) {
            $this->assertSame(0, $code->fresh()->used_count);
            $this->assertSame(UserStatus::Onboarding, $user->fresh()->status);
            $this->assertDatabaseCount('class_students', 0);
        }
    }

    public function test_finalization_revalidates_expired_code_inside_transaction(): void
    {
        [$user, $code] = $this->completeDraft(['expires_at' => now()->subMinute()]);

        $this->expectException(RegistrationCodeRejected::class);

        try {
            app(FinalizeStudentOnboarding::class)->execute(
                $user,
                $this->state($user, $code),
                $this->agreements(),
            );
        } finally {
            $this->assertSame(0, $code->fresh()->used_count);
            $this->assertDatabaseCount('class_students', 0);
        }
    }

    public function test_usage_limit_is_enforced_across_students(): void
    {
        $code = RegistrationCode::factory()->create(['max_uses' => 1]);
        [$firstUser] = $this->completeDraft([], $code);
        [$secondUser] = $this->completeDraft([], $code);
        $action = app(FinalizeStudentOnboarding::class);

        $action->execute($firstUser, $this->state($firstUser, $code), $this->agreements());

        $this->expectException(RegistrationCodeRejected::class);
        $action->execute($secondUser, $this->state($secondUser, $code), $this->agreements());
    }

    public function test_incomplete_draft_cannot_be_finalized(): void
    {
        [$user, $code] = $this->completeDraft();
        $user->onboardingResponse()->update([
            'device_access' => null,
            'expectation' => null,
        ]);

        $this->expectException(OnboardingFinalizationRejected::class);

        app(FinalizeStudentOnboarding::class)->execute(
            $user,
            $this->state($user, $code),
            $this->agreements(),
        );
    }

    /**
     * @param  array<string, mixed>  $codeAttributes
     * @return array{User, RegistrationCode}
     */
    private function completeDraft(array $codeAttributes = [], ?RegistrationCode $code = null): array
    {
        $user = User::factory()->create([
            'status' => UserStatus::Onboarding,
            'password' => null,
        ]);
        $code ??= RegistrationCode::factory()->create($codeAttributes);
        $schoolClass = $code->schoolClass ?? SchoolClass::factory()->create([
            'academic_year_id' => $code->academic_year_id,
        ]);

        StudentProfile::factory()->create([
            'user_id' => $user->id,
            'class_id' => $schoolClass->id,
            'membership_status' => StudentMembershipStatus::Onboarding,
        ]);
        StudentOnboardingResponse::factory()->create([
            'user_id' => $user->id,
            'current_step' => 5,
        ]);

        return [$user, $code];
    }

    private function state(User $user, RegistrationCode $code): array
    {
        return [
            'user_id' => $user->id,
            'registration_code_id' => $code->id,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    private function agreements(): array
    {
        return [
            'agree_rules' => true,
            'agree_privacy' => true,
            'agree_ai_policy' => true,
            'agree_publication_policy' => true,
        ];
    }
}
