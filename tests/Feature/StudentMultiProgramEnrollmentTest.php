<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\ClassStudent;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentMultiProgramEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_participant_can_join_second_program_with_registration_code(): void
    {
        [$student, $skuadBatch] = $this->activeStudentContext();
        [$creatorBatch, $creatorClass, $code] = $this->programContext('Konten Kreator', 'konten-kreator', 'Peserta Didik', 'KREATOR-GABUNG');

        $this->actingAs($student)
            ->get(route('student.programs.index'))
            ->assertOk()
            ->assertSee('Program Saya')
            ->assertSee($skuadBatch->program->name)
            ->assertDontSee($creatorBatch->program->name);

        $this->actingAs($student)
            ->post(route('student.programs.join'), ['code' => 'KREATOR-GABUNG'])
            ->assertRedirect(route('student.programs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('class_students', [
            'user_id' => $student->id,
            'program_batch_id' => $creatorBatch->id,
            'class_id' => $creatorClass->id,
            'status' => StudentMembershipStatus::Active->value,
        ]);
        $this->assertSame(1, $code->fresh()->used_count);

        $this->actingAs($student)
            ->get(route('student.programs.index'))
            ->assertOk()
            ->assertSee('Konten Kreator')
            ->assertSee('Peserta Didik')
            ->assertDontSee('KREATOR-GABUNG');
    }

    public function test_participant_cannot_join_same_program_twice_or_switch_to_unjoined_program(): void
    {
        [$student, $skuadBatch] = $this->activeStudentContext();
        [$creatorBatch] = $this->programContext('Konten Kreator', 'konten-kreator', 'Peserta', 'KREATOR-DUP');

        $this->actingAs($student)
            ->post(route('student.programs.join'), ['code' => 'KREATOR-DUP'])
            ->assertRedirect(route('student.programs.index'));

        $this->actingAs($student)
            ->post(route('student.programs.join'), ['code' => 'KREATOR-DUP'])
            ->assertSessionHasErrors('code');

        $this->withSession(['active_program_batch_id' => $skuadBatch->id])
            ->actingAs($student)
            ->put(route('program-context.update'), ['program_batch_id' => $creatorBatch->id])
            ->assertRedirect();

        $this->assertSame($creatorBatch->id, session('active_program_batch_id'));

        $unjoinedBatch = $this->programContext('Jurnalis Digital', 'jurnalis-digital', 'Peserta', 'JURNALIS-RAHASIA')[0];

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->put(route('program-context.update'), ['program_batch_id' => $unjoinedBatch->id])
            ->assertForbidden();

        $this->assertSame($creatorBatch->id, session('active_program_batch_id'));
    }

    /**
     * @return array{User, ProgramBatch}
     */
    private function activeStudentContext(): array
    {
        [$batch, $class] = $this->programContext('SKUAD', 'skuad', 'Siswa', 'SKUAD-AWAL');
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'name' => 'Nadia Multi Program',
            'status' => UserStatus::Active,
            'password' => null,
        ]);

        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'membership_status' => StudentMembershipStatus::Active,
        ]);
        ClassStudent::query()->create([
            'academic_year_id' => $class->academic_year_id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);

        return [$student->refresh(), $batch];
    }

    /**
     * @return array{ProgramBatch, SchoolClass, RegistrationCode}
     */
    private function programContext(string $name, string $slug, string $participantLabel, string $plainCode): array
    {
        $program = Program::query()->create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'pelatihan',
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'is_active' => true,
        ]);
        $institution = Institution::query()->firstOrCreate(
            ['slug' => 'rkdd-cikampek-selatan'],
            ['name' => 'RKDD Cikampek Selatan', 'type' => 'rkdd', 'is_active' => true],
        );
        $batch = ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => $name.' 2026',
            'slug' => $slug.'-2026',
            'period_label' => '2026',
            'audience_type' => 'community',
            'participant_label' => $participantLabel,
            'is_active' => true,
        ]);
        $year = AcademicYear::factory()->active()->create(['name' => $name.' 2026']);
        $class = SchoolClass::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'name' => 'Kelompok '.$name,
        ]);
        $code = RegistrationCode::factory()->forPlainText($plainCode)->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
        ]);

        return [$batch, $class, $code];
    }
}
