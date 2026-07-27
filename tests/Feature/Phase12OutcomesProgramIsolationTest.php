<?php

namespace Tests\Feature;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Enums\RoleSlug;
use App\Enums\ShowcaseHighlightPeriod;
use App\Enums\ShowcaseMediaType;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ClassStudent;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\MonthlyStudentAssessment;
use App\Models\PortfolioItem;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use App\Models\ShowcaseHighlight;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12OutcomesProgramIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_assessments_are_created_listed_and_authorized_per_active_program(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01']);
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-phase-12');
        [$creatorBatch, $creatorClass] = $this->programContext($year, 'Konten Kreator', 'creator-phase-12');
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Nadia Multi Asesmen']);
        $this->join($student, $skuadBatch, $skuadClass);
        $this->join($student, $creatorBatch, $creatorClass);

        $skuadAssessment = MonthlyStudentAssessment::query()->create([
            ...$this->assessmentData($year, $skuadClass, $student),
            'program_batch_id' => $skuadBatch->id,
            'period_label' => 'Juli 2026',
            'final_score' => 80,
            'achievement_level' => 3,
            'is_published' => true,
            'published_at' => now(),
            'assessed_by' => $teacher->id,
            'assessed_at' => now(),
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->post(route('teacher.monthly-assessments.store'), $this->assessmentData($year, $creatorClass, $student))
            ->assertRedirect();

        $created = MonthlyStudentAssessment::query()->where('program_batch_id', $creatorBatch->id)->firstOrFail();
        $this->assertSame($creatorBatch->id, $created->program_batch_id);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.monthly-assessments.index', ['academic_year_id' => $year->id, 'class_id' => $creatorClass->id]))
            ->assertOk()
            ->assertSee('Nadia Multi Asesmen')
            ->assertDontSee('SKUAD 2026');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.monthly-assessments.edit', $skuadAssessment))
            ->assertForbidden();
    }

    public function test_grades_and_portfolio_do_not_leak_across_student_active_program(): void
    {
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-grade-phase-12');
        [$creatorBatch, $creatorClass] = $this->programContext($year, 'Konten Kreator', 'creator-grade-phase-12');
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $this->join($student, $skuadBatch, $skuadClass);
        $this->join($student, $creatorBatch, $creatorClass);
        $skuadGrade = $this->publishedGrade($student, $year, $skuadBatch, $skuadClass, 'Nilai SKUAD');
        $creatorGrade = $this->publishedGrade($student, $year, $creatorBatch, $creatorClass, 'Nilai Creator');

        PortfolioItem::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'user_id' => $student->id,
            'title' => 'Portofolio SKUAD',
            'visibility' => PortfolioVisibility::School,
            'approval_status' => PortfolioApprovalStatus::Approved,
        ]);
        PortfolioItem::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $creatorBatch->id,
            'class_id' => $creatorClass->id,
            'user_id' => $student->id,
            'title' => 'Portofolio Creator',
            'visibility' => PortfolioVisibility::School,
            'approval_status' => PortfolioApprovalStatus::Approved,
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->get(route('student.grades.index'))
            ->assertOk()
            ->assertSee('Nilai Creator')
            ->assertDontSee('Nilai SKUAD');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->get(route('student.grades.show', $skuadGrade))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->get(route('student.grades.show', $creatorGrade))
            ->assertOk();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->get(route('student.portfolio.index'))
            ->assertOk()
            ->assertSee('Portofolio Creator')
            ->assertDontSee('Portofolio SKUAD');
    }

    public function test_public_showcase_can_filter_portfolio_by_program_and_staff_cannot_manage_other_program_highlight(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-showcase-phase-12');
        [$creatorBatch, $creatorClass] = $this->programContext($year, 'Konten Kreator', 'creator-showcase-phase-12');
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $this->join($student, $skuadBatch, $skuadClass);
        $this->join($student, $creatorBatch, $creatorClass);
        PortfolioItem::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'user_id' => $student->id,
            'title' => 'Karya Publik SKUAD',
            'visibility' => PortfolioVisibility::PublicApproved,
            'approval_status' => PortfolioApprovalStatus::Approved,
            'approved_at' => now(),
        ]);
        PortfolioItem::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $creatorBatch->id,
            'class_id' => $creatorClass->id,
            'user_id' => $student->id,
            'title' => 'Karya Publik Creator',
            'visibility' => PortfolioVisibility::PublicApproved,
            'approval_status' => PortfolioApprovalStatus::Approved,
            'approved_at' => now(),
        ]);
        $skuadHighlight = ShowcaseHighlight::query()->create([
            'program_batch_id' => $skuadBatch->id,
            'period' => ShowcaseHighlightPeriod::Weekly,
            'title' => 'Highlight SKUAD',
            'url' => 'https://example.com/skuad.png',
            'media_type' => ShowcaseMediaType::Image,
            'display_order' => 1,
            'is_active' => true,
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);

        $this->get(route('portfolio.public.index', ['program' => 'creator-showcase-phase-12']))
            ->assertOk()
            ->assertSee('Karya Publik Creator')
            ->assertDontSee('Karya Publik SKUAD');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('showcase-highlights.edit', $skuadHighlight))
            ->assertForbidden();
    }

    /**
     * @return array{ProgramBatch, SchoolClass}
     */
    private function programContext(AcademicYear $year, string $name, string $slug): array
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
            ['slug' => 'rkdd-phase-12'],
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
        $class = SchoolClass::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'name' => $name.' 2026',
        ]);

        return [$batch, $class];
    }

    private function join(User $student, ProgramBatch $batch, SchoolClass $class): void
    {
        ClassStudent::query()->create([
            'academic_year_id' => $class->academic_year_id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);
    }

    private function assessmentData(AcademicYear $year, SchoolClass $class, User $student): array
    {
        return [
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'semester' => 1,
            'assessment_month' => 1,
            'product_summary' => 'Bukti karya program.',
            'evidence_url' => 'https://example.com/evidence',
            'product_portfolio_score' => 80,
            'process_creativity_score' => 82,
            'collaboration_responsibility_score' => 84,
            'presentation_communication_score' => 86,
            'ethics_security_reflection_score' => 88,
            'strengths' => 'Konsisten.',
            'improvement_targets' => 'Perbaiki presentasi.',
            'remedial_plan' => '',
            'enrichment_plan' => '',
            'teacher_note' => '',
            'is_published' => 1,
        ];
    }

    private function publishedGrade(User $student, AcademicYear $year, ProgramBatch $batch, SchoolClass $class, string $title): Grade
    {
        $assignment = Assignment::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'title' => $title,
        ]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id]);

        return Grade::factory()->create([
            'submission_id' => $submission->id,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
