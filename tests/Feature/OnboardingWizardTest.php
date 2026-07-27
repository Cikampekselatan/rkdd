<?php

namespace Tests\Feature;

use App\Enums\OnboardingStep;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_wizard_requires_a_registration_code_validated_for_the_current_user(): void
    {
        $user = $this->onboardingUser();

        $this->actingAs($user)
            ->get(route('onboarding.wizard.show', OnboardingStep::Identity->value))
            ->assertRedirect(route('onboarding.registration-code.show'));

        $otherUser = $this->onboardingUser();
        $code = RegistrationCode::factory()->create();

        $this->actingAs($user)
            ->withSession(['onboarding.registration_code' => $this->codeState($otherUser, $code)])
            ->get(route('onboarding.wizard.show', OnboardingStep::Identity->value))
            ->assertRedirect(route('onboarding.registration-code.show'));
    }

    public function test_identity_step_renders_progress_and_form_fields(): void
    {
        [$user, $code] = $this->onboardingContext();

        $this->actingAs($user)
            ->withSession(['onboarding.registration_code' => $this->codeState($user, $code)])
            ->get(route('onboarding.wizard.show', OnboardingStep::Identity->value))
            ->assertOk()
            ->assertSee('Langkah 1 dari 5')
            ->assertSee('Nomor induk/ID peserta')
            ->assertSee('onboarding-progress', false);
    }

    public function test_student_cannot_skip_locked_steps(): void
    {
        [$user, $code] = $this->onboardingContext();

        $this->actingAs($user)
            ->withSession(['onboarding.registration_code' => $this->codeState($user, $code)])
            ->get(route('onboarding.wizard.show', OnboardingStep::Agreements->value))
            ->assertRedirect(route('onboarding.wizard.show', OnboardingStep::Identity->value));

        $this->actingAs($user)
            ->put(route('onboarding.wizard.guardian.update'), $this->guardianPayload())
            ->assertForbidden();
    }

    public function test_identity_step_saves_a_draft_and_enforces_code_class(): void
    {
        [$user, $code, $schoolClass] = $this->onboardingContext();
        $otherClass = SchoolClass::factory()->create(['academic_year_id' => $code->academic_year_id]);
        $session = ['onboarding.registration_code' => $this->codeState($user, $code)];

        $this->actingAs($user)->withSession($session)
            ->put(route('onboarding.wizard.identity.update'), [
                ...$this->identityPayload($otherClass->id),
            ])->assertSessionHasErrors('class_id');

        $this->actingAs($user)
            ->put(route('onboarding.wizard.identity.update'), [
                ...$this->identityPayload($schoolClass->id),
            ])->assertRedirect(route('onboarding.wizard.show', OnboardingStep::Guardian->value));

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $user->id,
            'student_number' => 'SKUAD-2026-001',
            'class_id' => $schoolClass->id,
            'membership_status' => StudentMembershipStatus::Onboarding->value,
        ]);
        $this->assertDatabaseHas('student_onboarding_responses', [
            'user_id' => $user->id,
            'current_step' => 2,
        ]);
    }

    public function test_all_five_steps_persist_and_finalize_the_student(): void
    {
        [$user, $code, $schoolClass] = $this->onboardingContext();

        $this->actingAs($user)
            ->withSession(['onboarding.registration_code' => $this->codeState($user, $code)]);

        $this->put(route('onboarding.wizard.identity.update'), [
            ...$this->identityPayload($schoolClass->id),
        ])->assertRedirect(route('onboarding.wizard.show', OnboardingStep::Guardian->value));

        $this->put(route('onboarding.wizard.guardian.update'), $this->guardianPayload())
            ->assertRedirect(route('onboarding.wizard.show', OnboardingStep::Access->value));

        $this->put(route('onboarding.wizard.access.update'), [
            'device_access' => ['android', 'laptop'],
            'internet_access' => 'stable',
            'willing_to_share_device' => 1,
            'digital_apps_text' => 'Canva, Google Docs',
        ])->assertRedirect(route('onboarding.wizard.show', OnboardingStep::Interests->value));

        $this->put(route('onboarding.wizard.interests.update'), [
            'interests' => ['design', 'photography'],
            'initial_skills' => ['presentation'],
            'experience' => 'Pernah membuat poster kelas.',
            'expectation' => 'Ingin belajar membuat karya yang lebih baik.',
            'learning_targets' => 'Membuat portofolio digital.',
        ])->assertRedirect(route('onboarding.wizard.show', OnboardingStep::Agreements->value));

        $this->get(route('onboarding.wizard.show', OnboardingStep::Agreements->value))
            ->assertOk()
            ->assertSee('Baca aturan')
            ->assertSee('AI sebagai alat bantu')
            ->assertSee('Penggunaan dan perlindungan data pribadi');

        $this->post(route('onboarding.wizard.agreements.finalize'), $this->agreementsPayload())
            ->assertRedirect(route('student.dashboard'));

        $user->refresh();
        $response = $user->onboardingResponse;

        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue($user->hasRole(RoleSlug::Student));
        $this->assertSame(StudentMembershipStatus::Active, $user->studentProfile->membership_status);
        $this->assertNotNull($user->studentProfile->joined_at);
        $this->assertNotNull($response->completed_at);
        $this->assertSame($code->id, $response->registration_code_id);
        $this->assertSame(['android', 'laptop'], $response->device_access);
        $this->assertSame(['Canva', 'Google Docs'], $response->digital_apps);
        $this->assertSame(1, $code->fresh()->used_count);
        $this->assertDatabaseHas('class_students', [
            'academic_year_id' => $code->academic_year_id,
            'class_id' => $schoolClass->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_each_step_has_server_side_validation(): void
    {
        [$user, $code, $schoolClass] = $this->onboardingContext();
        $this->actingAs($user)->withSession(['onboarding.registration_code' => $this->codeState($user, $code)]);

        $this->put(route('onboarding.wizard.identity.update'), [])->assertSessionHasErrors([
            'name', 'student_number', 'gender', 'birth_date', 'class_id',
        ]);

        $this->put(route('onboarding.wizard.identity.update'), $this->identityPayload($schoolClass->id));
        $this->put(route('onboarding.wizard.guardian.update'), [
            'parent_name' => '',
            'parent_phone' => 'abc',
            'guardian_relationship' => '',
        ])->assertSessionHasErrors(['parent_name', 'parent_phone', 'guardian_relationship']);

        $this->put(route('onboarding.wizard.guardian.update'), $this->guardianPayload());
        $this->put(route('onboarding.wizard.access.update'), [])->assertSessionHasErrors([
            'device_access', 'internet_access',
        ]);

        $this->put(route('onboarding.wizard.access.update'), [
            'device_access' => ['android'],
            'internet_access' => 'limited',
            'willing_to_share_device' => 0,
        ]);
        $this->put(route('onboarding.wizard.interests.update'), [])->assertSessionHasErrors([
            'interests', 'expectation', 'learning_targets',
        ]);

        $this->put(route('onboarding.wizard.interests.update'), [
            'interests' => ['coding'],
            'expectation' => 'Belajar coding.',
            'learning_targets' => 'Membuat proyek.',
        ]);
        $this->post(route('onboarding.wizard.agreements.finalize'), [])->assertSessionHasErrors([
            'agree_rules', 'agree_privacy', 'agree_ai_policy', 'agree_publication_policy',
        ]);
    }

    private function onboardingUser(): User
    {
        return User::factory()->create([
            'status' => UserStatus::Onboarding,
            'password' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $codeAttributes
     * @return array{User, RegistrationCode, SchoolClass}
     */
    private function onboardingContext(array $codeAttributes = []): array
    {
        $academicYear = AcademicYear::factory()->create();
        $schoolClass = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id]);
        $code = RegistrationCode::factory()->create([
            'academic_year_id' => $academicYear->id,
            'class_id' => $schoolClass->id,
            ...$codeAttributes,
        ]);

        return [$this->onboardingUser(), $code, $schoolClass];
    }

    /**
     * @return array<string, mixed>
     */
    private function codeState(User $user, RegistrationCode $code): array
    {
        return [
            'user_id' => $user->id,
            'registration_code_id' => $code->id,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    private function identityPayload(int $classId): array
    {
        return [
            'name' => 'Nadia Putri Ramadhani',
            'nickname' => 'Nadia',
            'student_number' => 'SKUAD-2026-001',
            'nisn' => '1234567890',
            'gender' => 'female',
            'birth_date' => '2012-04-18',
            'grade_level' => 8,
            'school_class_name' => '8B',
            'class_id' => $classId,
        ];
    }

    private function guardianPayload(): array
    {
        return [
            'parent_name' => 'Siti Rahmawati',
            'parent_phone' => '081234567890',
            'guardian_relationship' => 'Ibu',
            'address' => 'Jatisari',
        ];
    }

    private function agreementsPayload(): array
    {
        return [
            'agree_rules' => 1,
            'agree_privacy' => 1,
            'agree_ai_policy' => 1,
            'agree_publication_policy' => 1,
        ];
    }
}
