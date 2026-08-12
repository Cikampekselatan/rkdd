<?php

namespace Tests\Feature;

use App\Enums\AssignmentType;
use App\Enums\PortfolioVisibility;
use App\Enums\RoleSlug;
use App\Enums\SubmissionStatus;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ClassStudent;
use App\Models\Institution;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\PortfolioItem;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_assignment_creates_student_bell_notification_and_can_be_read(): void
    {
        [$teacher, $student, $year, $class, $session] = $this->learningContext();

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), [
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'learning_session_id' => $session->id,
            'title' => 'Tugas Poster Digital',
            'instructions' => 'Buat poster etika digital.',
            'type' => AssignmentType::Text->value,
            'due_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'allow_late' => 1,
            'max_files' => 0,
            'max_file_size_kb' => 1000,
            'max_revisions' => 1,
            'is_published' => 1,
        ])->assertRedirect();

        $notification = $student->notifications()->firstOrFail();
        $this->assertSame('assignment', $notification->data['kind']);
        $this->assertSame('Tugas baru: Tugas Poster Digital', $notification->data['title']);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Tugas baru: Tugas Poster Digital');

        $this->actingAs($student)->get(route('interactions.notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_grade_revision_and_portfolio_events_create_notifications_for_the_right_roles(): void
    {
        [$teacher, $student, $year, $class, $session] = $this->learningContext();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $rubric = $this->rubric($year);
        $assignment = Assignment::factory()->create([
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'learning_session_id' => $session->id,
            'rubric_id' => $rubric->id,
            'is_published' => true,
        ]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id, 'status' => SubmissionStatus::UnderReview]);
        SubmissionVersion::create(['submission_id' => $submission->id, 'version_number' => 1, 'text_content' => 'Karya final', 'submitted_at' => now()]);

        $this->actingAs($teacher)->put(route('teacher.grades.update', $submission), [
            'scores' => $rubric->criteria->map(fn ($criterion) => ['criterion_id' => $criterion->id, 'level' => 3, 'teacher_note' => 'Baik'])->all(),
            'feedback' => 'Karya sudah rapi.',
            'private_note' => '',
            'action' => 'publish',
            'revision_note' => '',
            'remedial_status' => 'none',
            'remedial_note' => '',
            'remedial_due_at' => '',
        ])->assertSessionHas('success');

        $this->assertTrue($student->notifications()->where('data->kind', 'grade')->exists());

        $portfolio = PortfolioItem::factory()->create([
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'visibility' => PortfolioVisibility::PublicApproved,
        ]);

        $this->actingAs($coach)->patch(route('teacher.portfolio.review', $portfolio), [
            'decision' => 'approved',
            'approval_note' => 'Layak tampil.',
        ])->assertSessionHas('success');

        $this->assertTrue($student->notifications()->where('data->kind', 'portfolio_review')->exists());
    }

    public function test_header_and_read_all_notifications_are_scoped_to_active_program(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$skuadBatch, $creatorBatch] = $this->notificationPrograms($teacher);

        $skuadNotification = DatabaseNotification::query()->create([
            'id' => (string) str()->uuid(),
            'type' => 'database',
            'notifiable_type' => User::class,
            'notifiable_id' => $teacher->id,
            'data' => [
                'kind' => 'announcement',
                'title' => 'Info SKUAD',
                'url' => route('teacher.dashboard'),
                'program_batch_id' => $skuadBatch->id,
            ],
        ]);
        $creatorNotification = DatabaseNotification::query()->create([
            'id' => (string) str()->uuid(),
            'type' => 'database',
            'notifiable_type' => User::class,
            'notifiable_id' => $teacher->id,
            'data' => [
                'kind' => 'announcement',
                'title' => 'Info Konten Kreator',
                'url' => route('teacher.dashboard'),
                'program_batch_id' => $creatorBatch->id,
            ],
        ]);

        $this->withSession(['active_program_batch_id' => $skuadBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Info SKUAD')
            ->assertDontSee('Info Konten Kreator');

        $this->withSession(['active_program_batch_id' => $skuadBatch->id])
            ->actingAs($teacher)
            ->post(route('interactions.notifications.read-all'))
            ->assertSessionHas('success');

        $this->assertNotNull($skuadNotification->fresh()->read_at);
        $this->assertNull($creatorNotification->fresh()->read_at);

        $this->withSession(['active_program_batch_id' => $skuadBatch->id])
            ->actingAs($teacher)
            ->get(route('interactions.notifications.read', $creatorNotification->id))
            ->assertNotFound();
    }

    private function learningContext(): array
    {
        $year = AcademicYear::factory()->active()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id]);
        ClassStudent::create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'user_id' => $student->id, 'joined_at' => now(), 'status' => 'active']);
        $module = LearningModule::factory()->create(['academic_year_id' => $year->id]);
        $session = LearningSession::factory()->create(['academic_year_id' => $year->id, 'learning_module_id' => $module->id]);

        return [$teacher, $student, $year, $class, $session];
    }

    private function rubric(AcademicYear $year): Rubric
    {
        $rubric = Rubric::factory()->create(['academic_year_id' => $year->id]);
        $criterion = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kualitas karya', 'weight' => 100, 'sort_order' => 1]);
        foreach ([1, 2, 3, 4] as $level) {
            $criterion->levels()->create(['level' => $level, 'label' => 'Level '.$level, 'description' => 'Deskripsi '.$level]);
        }

        return $rubric->refresh()->load('criteria.levels');
    }

    /** @return array{ProgramBatch, ProgramBatch} */
    private function notificationPrograms(User $teacher): array
    {
        $institution = Institution::query()->create([
            'name' => 'RKDD Notifikasi',
            'slug' => 'rkdd-notifikasi',
            'type' => 'rkdd',
            'is_active' => true,
        ]);

        $skuad = $this->programBatch($institution, 'SKUAD Notifikasi', 'skuad-notifikasi');
        $creator = $this->programBatch($institution, 'Konten Kreator Notifikasi', 'creator-notifikasi');
        $teacher->assignedProgramBatches()->attach([$skuad->id, $creator->id]);

        return [$skuad, $creator];
    }

    private function programBatch(Institution $institution, string $name, string $slug): ProgramBatch
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

        return ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => $name.' 2026',
            'slug' => $slug.'-2026',
            'period_label' => '2026',
            'audience_type' => 'community',
            'participant_label' => 'Peserta',
            'is_active' => true,
        ]);
    }
}
