<?php

namespace Tests\Feature;

use App\Enums\OnboardingStep;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPreRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pre_registration_draft_is_applied_after_code_when_group_is_known(): void
    {
        $student = User::factory()->create([
            'status' => UserStatus::Onboarding,
            'password' => null,
        ]);
        $academicYear = AcademicYear::factory()->active()->create();
        $batch = $this->programBatch('SKUAD Digital');
        $group = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id, 'program_batch_id' => $batch->id]);
        $plainCode = 'SKUAD-ABCDE-FGHIJ-KLMNO-PQRST';
        $code = RegistrationCode::factory()
            ->forPlainText($plainCode)
            ->create([
                'academic_year_id' => $academicYear->id,
                'program_batch_id' => $batch->id,
                'class_id' => $group->id,
            ]);

        $draft = [
            'intended_program_batch_id' => $batch->id,
            'name' => 'Nadia Putri',
            'nickname' => 'Nadia',
            'student_number' => 'SIS-001',
            'nisn' => '12345678',
            'gender' => 'female',
            'birth_date' => now()->subYears(13)->format('Y-m-d'),
            'grade_level' => 8,
            'school_class_name' => '8B',
            'parent_name' => 'Ibu Nadia',
            'parent_phone' => '081234567890',
            'guardian_relationship' => 'Ibu',
            'address' => 'Jatisari',
            'device_access' => ['android', 'laptop'],
            'internet_access' => 'stable',
            'willing_to_share_device' => true,
            'digital_apps' => ['Canva', 'CapCut'],
            'interests' => ['design', 'video'],
            'initial_skills' => ['design'],
            'experience' => 'Pernah membuat poster.',
            'expectation' => 'Ingin belajar membuat karya digital.',
            'learning_targets' => 'Bisa membuat portofolio digital.',
        ];

        $this->actingAs($student)
            ->withSession(['student.pre_registration' => $draft])
            ->post(route('onboarding.registration-code.store'), ['code' => $plainCode])
            ->assertRedirect(route('onboarding.registration-code.accepted'));

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'student_number' => 'SIS-001',
            'school_class_name' => '8B',
            'class_id' => $group->id,
            'parent_name' => 'Ibu Nadia',
        ]);
        $this->assertDatabaseHas('student_onboarding_responses', [
            'user_id' => $student->id,
            'registration_code_id' => $code->id,
            'current_step' => OnboardingStep::Agreements->number(),
        ]);
        $this->assertSame('Nadia Putri', $student->fresh()->name);

        $this->actingAs($student)
            ->withSession([
                'onboarding.registration_code' => [
                    'user_id' => $student->id,
                    'registration_code_id' => $code->id,
                    'validated_at' => now()->toIso8601String(),
                ],
            ])
            ->get(route('onboarding.registration-code.accepted'))
            ->assertOk()
            ->assertSee('Lanjut persetujuan akhir');
    }

    public function test_pre_registration_rejects_code_from_different_program_choice(): void
    {
        $student = User::factory()->create([
            'status' => UserStatus::Onboarding,
            'password' => null,
        ]);
        $academicYear = AcademicYear::factory()->active()->create();
        $chosenBatch = $this->programBatch('Konten Kreator');
        $otherBatch = $this->programBatch('SKUAD Digital');
        $group = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id, 'program_batch_id' => $otherBatch->id]);
        $plainCode = 'SKUAD-ZZZZZ-YYYYY-XXXXX-WWWWW';
        RegistrationCode::factory()
            ->forPlainText($plainCode)
            ->create([
                'academic_year_id' => $academicYear->id,
                'program_batch_id' => $otherBatch->id,
                'class_id' => $group->id,
            ]);

        $this->actingAs($student)
            ->withSession(['student.pre_registration' => [
                'intended_program_batch_id' => $chosenBatch->id,
                'name' => 'Nadia Putri',
                'student_number' => 'SIS-002',
                'gender' => 'female',
                'birth_date' => now()->subYears(13)->format('Y-m-d'),
                'grade_level' => 8,
                'school_class_name' => '8B',
                'parent_name' => 'Ibu Nadia',
                'parent_phone' => '081234567890',
                'guardian_relationship' => 'Ibu',
                'device_access' => ['android'],
                'internet_access' => 'stable',
                'willing_to_share_device' => true,
                'digital_apps' => [],
                'interests' => ['design'],
                'expectation' => 'Ingin belajar.',
                'learning_targets' => 'Bisa berkarya.',
            ]])
            ->post(route('onboarding.registration-code.store'), ['code' => $plainCode])
            ->assertSessionHasErrors('code');
    }

    private function programBatch(string $programName): ProgramBatch
    {
        $program = Program::query()->create([
            'name' => $programName,
            'slug' => str($programName)->slug().'-'.str()->random(6),
            'type' => 'pelatihan',
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'is_active' => true,
        ]);
        $institution = Institution::query()->create([
            'name' => 'RKDD Cikampek Selatan '.$program->id,
            'slug' => 'rkdd-test-'.$program->id,
            'type' => 'rkdd',
            'is_active' => true,
        ]);

        return ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => $programName.' 2026',
            'slug' => str($programName)->slug().'-2026-'.$program->id,
            'period_label' => '2026',
            'audience_type' => 'community',
            'participant_label' => 'Peserta',
            'is_active' => true,
        ]);
    }
}
