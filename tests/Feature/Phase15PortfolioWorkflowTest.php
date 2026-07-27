<?php

namespace Tests\Feature;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ClassStudent;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\PortfolioItem;
use App\Models\PortfolioWorkTypeOption;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase15PortfolioWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_creates_updates_and_deletes_independent_portfolio_with_private_files(): void
    {
        Storage::fake('local');
        [$student] = $this->studentContext();

        $this->actingAs($student)->post(route('student.portfolio.store'), $this->payload([
            'thumbnail' => UploadedFile::fake()->image('cover.png'),
            'final_file' => UploadedFile::fake()->create('hasil akhir.pdf', 120, 'application/pdf'),
        ]))->assertRedirect();

        $item = PortfolioItem::firstOrFail();
        $this->assertStringNotContainsString('hasil akhir', $item->final_file_path);
        Storage::disk('local')->assertExists($item->thumbnail_path);
        Storage::disk('local')->assertExists($item->final_file_path);
        $this->actingAs($student)->get(route('portfolio.assets', [$item, 'final']))->assertOk();

        $this->actingAs($student)->put(route('student.portfolio.update', $item), $this->payload(['title' => 'Poster Literasi Baru']))->assertSessionHas('success');
        $this->assertSame(PortfolioApprovalStatus::Pending, $item->fresh()->approval_status);
        $this->actingAs($student)->get(route('student.portfolio.print', $item))->assertOk()->assertSee('Poster Literasi Baru');

        $finalPath = $item->fresh()->final_file_path;
        $this->actingAs($student)->delete(route('student.portfolio.destroy', $item))->assertSessionHas('success');
        $this->assertSoftDeleted($item);
        Storage::disk('local')->assertMissing($finalPath);
    }

    public function test_graded_portfolio_requires_own_published_grade_and_cannot_be_duplicated(): void
    {
        [$student, $class, $year] = $this->studentContext();
        [$other] = $this->studentContext();
        $assignment = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id]);
        SubmissionVersion::create(['submission_id' => $submission->id, 'version_number' => 1, 'text_content' => 'Proses', 'submitted_at' => now()]);
        Grade::factory()->create(['submission_id' => $submission->id, 'is_published' => true, 'published_at' => now()]);

        $gradedPayload = $this->payload(['source_type' => 'graded', 'submission_id' => $submission->id, 'final_url' => '']);
        $this->actingAs($other)->post(route('student.portfolio.store'), $gradedPayload)->assertSessionHasErrors('submission_id');
        $this->actingAs($student)->post(route('student.portfolio.store'), $gradedPayload)->assertRedirect();
        $this->actingAs($student)->post(route('student.portfolio.store'), $gradedPayload)->assertSessionHasErrors('submission_id');

        $item = PortfolioItem::firstOrFail();
        $this->assertSame($submission->id, $item->submission_id);
        $this->assertSame($submission->versions()->first()->id, $item->final_submission_version_id);
    }

    public function test_visibility_policy_prevents_cross_user_and_cross_class_leakage(): void
    {
        [$owner, $class, $year] = $this->studentContext();
        [$classmate] = $this->studentContext($class, $year);
        [$outsider] = $this->studentContext();
        $private = PortfolioItem::factory()->create(['user_id' => $owner->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);
        $classItem = PortfolioItem::factory()->create(['user_id' => $owner->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'visibility' => PortfolioVisibility::ClassRoom, 'approval_status' => PortfolioApprovalStatus::Approved]);
        $pending = PortfolioItem::factory()->create(['user_id' => $owner->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'visibility' => PortfolioVisibility::School, 'approval_status' => PortfolioApprovalStatus::Pending]);

        $this->actingAs($owner)->get(route('student.portfolio.show', $private))->assertOk();
        $this->actingAs($classmate)->get(route('student.portfolio.show', $private))->assertForbidden();
        $this->actingAs($classmate)->get(route('student.portfolio.show', $classItem))->assertOk();
        $this->actingAs($outsider)->get(route('student.portfolio.show', $classItem))->assertForbidden();
        $this->actingAs($classmate)->get(route('student.portfolio.show', $pending))->assertForbidden();
    }

    public function test_teacher_can_approve_reject_feature_and_actions_are_audited(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$student, $class, $year] = $this->studentContext();
        $item = PortfolioItem::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'visibility' => PortfolioVisibility::PublicApproved, 'approval_status' => PortfolioApprovalStatus::Pending]);

        $this->actingAs($teacher)->patch(route('teacher.portfolio.review', $item), ['decision' => 'approved'])->assertSessionHas('success');
        $this->assertSame(PortfolioApprovalStatus::Approved, $item->fresh()->approval_status);
        $this->actingAs($teacher)->patch(route('teacher.portfolio.feature', $item))->assertSessionHas('success');
        $this->assertTrue($item->fresh()->is_featured);
        $this->assertDatabaseHas('portfolio_item_audits', ['portfolio_item_id' => $item->id, 'event' => 'approved']);
        $this->assertDatabaseHas('portfolio_item_audits', ['portfolio_item_id' => $item->id, 'event' => 'featured']);

        $this->actingAs($teacher)->patch(route('teacher.portfolio.review', $item), ['decision' => 'rejected', 'approval_note' => 'Sumber visual perlu dilengkapi.'])->assertSessionHas('success');
        $this->assertFalse($item->fresh()->is_featured);
    }

    public function test_update_resets_shared_approval_and_featured_status(): void
    {
        [$student, $class, $year] = $this->studentContext();
        $item = PortfolioItem::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'visibility' => PortfolioVisibility::School, 'approval_status' => PortfolioApprovalStatus::Approved, 'is_featured' => true, 'final_url' => 'https://example.com/old']);

        $this->actingAs($student)->put(route('student.portfolio.update', $item), $this->payload(['title' => 'Versi yang diperbarui', 'final_url' => 'https://example.com/new']))->assertSessionHas('success');
        $item->refresh();
        $this->assertSame(PortfolioApprovalStatus::Pending, $item->approval_status);
        $this->assertFalse($item->is_featured);
        $this->assertNull($item->approved_at);
    }

    public function test_public_showcase_and_assets_only_expose_approved_public_items(): void
    {
        Storage::fake('local');
        [$student, $class, $year] = $this->studentContext();
        Storage::disk('local')->put('portfolios/public/final.pdf', 'public work');
        $approved = PortfolioItem::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'title' => 'Karya Publik Aman', 'visibility' => PortfolioVisibility::PublicApproved, 'approval_status' => PortfolioApprovalStatus::Approved, 'final_file_path' => 'portfolios/public/final.pdf', 'final_original_name' => 'karya.pdf']);
        $pending = PortfolioItem::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'title' => 'Karya Masih Pending', 'visibility' => PortfolioVisibility::PublicApproved, 'approval_status' => PortfolioApprovalStatus::Pending]);

        $this->get(route('portfolio.public.index'))->assertOk()->assertSee('Karya Publik Aman')->assertDontSee('Karya Masih Pending');
        $this->get(route('portfolio.public.show', $approved))->assertOk();
        $this->get(route('portfolio.public.show', $pending))->assertNotFound();
        $this->get(route('portfolio.assets', [$approved, 'final']))->assertOk();
        $this->get(route('portfolio.assets', [$pending, 'final']))->assertForbidden();
        $this->get(route('portfolio.assets', [$approved, 'unknown']))->assertNotFound();
    }

    public function test_ai_declaration_and_teacher_filter_pages_validate_and_render(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$student, $class, $year] = $this->studentContext();
        $this->actingAs($student)->post(route('student.portfolio.store'), $this->payload(['ai_used' => 1, 'ai_tools' => '', 'ai_usage_description' => '']))->assertSessionHasErrors(['ai_tools', 'ai_usage_description']);
        PortfolioItem::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'title' => 'Poster Filter']);
        $this->actingAs($teacher)->get(route('teacher.portfolio.index', ['q' => 'Poster Filter', 'work_type' => 'poster']))->assertOk()->assertSee('Poster Filter');
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->assertSee('Karya portofolio');
    }

    public function test_portfolio_work_types_follow_active_program_and_can_be_managed(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $year = AcademicYear::factory()->active()->create(['name' => '2026/2027']);
        $institution = Institution::query()->create([
            'name' => 'RKDD Cikampek Selatan',
            'slug' => 'rkdd-cikampek-selatan',
            'type' => 'rkdd',
            'is_active' => true,
        ]);
        $program = Program::query()->create([
            'name' => 'Jurnalistik & Media Kreatif',
            'slug' => 'jurnalistik-media-kreatif',
            'type' => 'pelatihan',
            'primary_color' => '#1d4ed8',
            'secondary_color' => '#0f172a',
            'accent_color' => '#06b6d4',
            'is_active' => true,
        ]);
        $batch = ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => 'Jurnalistik 2026/2027',
            'slug' => 'jurnalistik-20262027',
            'period_label' => $year->name,
            'audience_type' => 'community',
            'participant_label' => 'Peserta',
            'is_active' => true,
        ]);
        $coach->assignedProgramBatches()->attach($batch, ['assigned_by' => $superAdmin->id]);
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'program_batch_id' => $batch->id, 'name' => 'Jurnalistik 2026/2027']);
        [$student] = $this->studentContext($class, $year, $batch);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.portfolio-work-types.index', ['program_id' => $program->id]))
            ->assertOk()
            ->assertSee('Artikel berita');

        $this->actingAs($superAdmin)
            ->post(route('super-admin.portfolio-work-types.store'), [
                'program_id' => $program->id,
                'name' => 'Naskah Feature',
                'slug' => '',
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect(route('super-admin.portfolio-work-types.index', ['program_id' => $program->id]));

        $this->assertDatabaseHas('portfolio_work_type_options', [
            'program_id' => $program->id,
            'slug' => 'naskah_feature',
            'name' => 'Naskah Feature',
        ]);

        $this->actingAs($student)
            ->withSession(['active_program_batch_id' => $batch->id])
            ->get(route('student.portfolio.create'))
            ->assertOk()
            ->assertSee('Artikel berita')
            ->assertSee('Naskah Feature');

        $this->actingAs($student)
            ->withSession(['active_program_batch_id' => $batch->id])
            ->post(route('student.portfolio.store'), $this->payload([
                'title' => 'Feature Profil Warga',
                'work_type' => 'naskah_feature',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('portfolio_items', [
            'program_batch_id' => $batch->id,
            'work_type' => 'naskah_feature',
        ]);

        $this->actingAs($coach)
            ->withSession(['active_program_batch_id' => $batch->id])
            ->get(route('teacher.portfolio.index', ['work_type' => 'naskah_feature']))
            ->assertOk()
            ->assertSee('Feature Profil Warga')
            ->assertSee('Naskah Feature');
    }

    private function studentContext(?SchoolClass $class = null, ?AcademicYear $year = null, ?ProgramBatch $batch = null): array
    {
        $year ??= AcademicYear::factory()->create();
        $class ??= SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'program_batch_id' => $batch?->id ?? $class->program_batch_id]);
        ClassStudent::create(['academic_year_id' => $year->id, 'program_batch_id' => $batch?->id ?? $class->program_batch_id, 'class_id' => $class->id, 'user_id' => $student->id, 'joined_at' => now(), 'status' => 'active']);

        return [$student, $class, $year];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'source_type' => 'independent',
            'submission_id' => '',
            'title' => 'Poster Literasi Digital',
            'work_type' => 'poster',
            'description' => 'Poster edukasi tentang cara menjaga jejak digital.',
            'reflection' => 'Saya belajar menyederhanakan pesan menjadi visual.',
            'sources' => 'Modul SKUAD',
            'ai_used' => 0,
            'ai_tools' => '',
            'ai_usage_description' => '',
            'visibility' => 'school',
            'initial_url' => '',
            'final_url' => 'https://example.com/karya',
        ], $overrides);
    }
}
