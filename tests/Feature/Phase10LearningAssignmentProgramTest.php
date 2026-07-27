<?php

namespace Tests\Feature;

use App\Enums\LearningSessionStatus;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ClassStudent;
use App\Models\Institution;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\Rubric;
use App\Models\SchoolClass;
use App\Models\User;
use App\Notifications\SkuadActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Phase10LearningAssignmentProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_accesses_learning_items_from_active_program(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$skuadBatch, $skuadClass, $skuadSession, $skuadRubric] = $this->programContext('SKUAD', 'skuad-phase-10');
        [$creatorBatch, $creatorClass, $creatorSession, $creatorRubric] = $this->programContext('Konten Kreator', 'creator-phase-10');
        $skuadAssignment = Assignment::factory()->create([
            'academic_year_id' => $skuadClass->academic_year_id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'learning_session_id' => $skuadSession->id,
            'rubric_id' => $skuadRubric->id,
            'title' => 'Tugas SKUAD Lintas Program',
        ]);
        $creatorAssignment = Assignment::factory()->create([
            'academic_year_id' => $creatorClass->academic_year_id,
            'program_batch_id' => $creatorBatch->id,
            'class_id' => $creatorClass->id,
            'learning_session_id' => $creatorSession->id,
            'rubric_id' => $creatorRubric->id,
            'title' => 'Tugas Creator Aktif',
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.assignments.index'))
            ->assertOk()
            ->assertSee('Tugas Creator Aktif')
            ->assertDontSee('Tugas SKUAD Lintas Program');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.assignments.show', $skuadAssignment))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.assignments.show', $creatorAssignment))
            ->assertOk();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.learning.sessions.preview', $skuadSession))
            ->assertForbidden();
    }

    public function test_rubrics_are_created_and_listed_inside_active_program(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$skuadBatch, , , $skuadRubric] = $this->programContext('SKUAD', 'skuad-rubric-phase-10');
        [$creatorBatch] = $this->programContext('Konten Kreator', 'creator-rubric-phase-10');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->post(route('teacher.rubrics.store'), $this->rubricPayload('Rubrik Creator Baru'))
            ->assertRedirect();

        $created = Rubric::query()->where('name', 'Rubrik Creator Baru')->firstOrFail();
        $this->assertSame($creatorBatch->id, $created->program_batch_id);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.rubrics.index'))
            ->assertOk()
            ->assertSee('Rubrik Creator Baru')
            ->assertDontSee($skuadRubric->name);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.rubrics.show', $skuadRubric))
            ->assertForbidden();

        $this->assertSame($skuadBatch->id, $skuadRubric->program_batch_id);
    }

    public function test_assignment_cannot_mix_class_session_and_rubric_from_different_programs(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [, $skuadClass] = $this->programContext('SKUAD', 'skuad-mix-phase-10');
        [$creatorBatch, , $creatorSession, $creatorRubric] = $this->programContext('Konten Kreator', 'creator-mix-phase-10');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->post(route('teacher.assignments.store'), [
                ...$this->assignmentPayload($skuadClass, $creatorSession),
                'rubric_id' => $creatorRubric->id,
            ])
            ->assertSessionHasErrors(['class_id']);
    }

    public function test_published_assignment_notification_stays_inside_assignment_program(): void
    {
        Notification::fake();

        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$batch, $class, $session] = $this->programContext('SKUAD', 'skuad-notify-phase-10');
        [$otherBatch] = $this->programContext('Konten Kreator', 'creator-notify-phase-10');
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $otherProgramStudent = User::factory()->withRole(RoleSlug::Student)->create();
        ClassStudent::query()->create([
            'academic_year_id' => $class->academic_year_id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);
        ClassStudent::query()->create([
            'academic_year_id' => $class->academic_year_id,
            'program_batch_id' => $otherBatch->id,
            'class_id' => $class->id,
            'user_id' => $otherProgramStudent->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);

        $this->withSession(['active_program_batch_id' => $batch->id])
            ->actingAs($teacher)
            ->post(route('teacher.assignments.store'), $this->assignmentPayload($class, $session))
            ->assertRedirect();

        Notification::assertSentTo($student, SkuadActivityNotification::class);
        Notification::assertNotSentTo($otherProgramStudent, SkuadActivityNotification::class);
    }

    /**
     * @return array{ProgramBatch, SchoolClass, LearningSession, Rubric}
     */
    private function programContext(string $name, string $slug): array
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
            ['slug' => 'rkdd-phase-10'],
            ['name' => 'RKDD Cikampek Selatan', 'type' => 'rkdd', 'is_active' => true],
        );
        $batch = ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => $name.' 2026',
            'slug' => $slug.'-2026',
            'period_label' => '2026',
            'audience_type' => 'community',
            'participant_label' => 'Peserta',
            'is_active' => true,
        ]);
        $year = AcademicYear::factory()->active()->create(['name' => $name.' 2026']);
        $class = SchoolClass::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'name' => 'Kelompok '.$name,
        ]);
        $module = LearningModule::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'title' => 'Modul '.$name,
        ]);
        $session = LearningSession::factory()->create([
            'learning_module_id' => $module->id,
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'session_number' => 1,
            'title' => 'Pertemuan '.$name,
            'slug' => str('Pertemuan '.$name)->slug().'-'.$batch->id,
            'status' => LearningSessionStatus::Published,
            'published_at' => now(),
        ]);
        $rubric = Rubric::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'name' => 'Rubrik '.$name,
        ]);

        return [$batch, $class, $session, $rubric];
    }

    private function assignmentPayload(SchoolClass $class, LearningSession $session): array
    {
        return [
            'academic_year_id' => $class->academic_year_id,
            'class_id' => $class->id,
            'learning_session_id' => $session->id,
            'title' => 'Proyek Etika Digital',
            'instructions' => 'Buat karya tentang keamanan akun.',
            'type' => 'mixed',
            'available_from' => now()->subHour()->format('Y-m-d H:i:s'),
            'due_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'allow_late' => 1,
            'max_files' => 3,
            'max_file_size_kb' => 5120,
            'allowed_mime_types_text' => 'application/pdf, image/png',
            'max_revisions' => 2,
            'is_published' => 1,
        ];
    }

    private function rubricPayload(string $name): array
    {
        return [
            'name' => $name,
            'description' => 'Rubrik per program',
            'is_active' => 1,
            'criteria' => [
                ['name' => 'Pemahaman', 'weight' => 40, 'levels' => ['Dasar', 'Berkembang', 'Terampil', 'Mandiri']],
                ['name' => 'Kreativitas', 'weight' => 60, 'levels' => ['Dasar', 'Berkembang', 'Terampil', 'Mandiri']],
            ],
        ];
    }
}
