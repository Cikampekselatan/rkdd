<?php

namespace Tests\Feature;

use App\Enums\DocumentAudience;
use App\Enums\DocumentCategory;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\ActivityDocumentation;
use App\Models\ClassStudent;
use App\Models\DocumentResource;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase14DocumentProgramIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_center_is_scoped_to_active_program_for_lists_direct_access_and_actions(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch] = $this->programContext($year, 'SKUAD', 'skuad-doc-phase-14');
        [$creatorBatch] = $this->programContext($year, 'Konten Kreator', 'creator-doc-phase-14');

        $skuadDocument = DocumentResource::factory()->published(DocumentAudience::Students)->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'title' => 'Panduan SKUAD Program',
        ]);
        $creatorDocument = DocumentResource::factory()->published(DocumentAudience::Students)->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $creatorBatch->id,
            'title' => 'Panduan Creator Program',
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($admin)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('Panduan Creator Program')
            ->assertDontSee('Panduan SKUAD Program');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($admin)
            ->get(route('documents.show', $skuadDocument))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($superAdmin)
            ->patch(route('documents.pin', $skuadDocument))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($superAdmin)
            ->patch(route('documents.pin', $creatorDocument))
            ->assertSessionHas('success');

        $this->assertTrue($creatorDocument->fresh()->is_pinned);
    }

    public function test_student_documents_follow_active_membership_program_and_year(): void
    {
        $skuadYear = AcademicYear::factory()->create(['name' => '2025/2026']);
        $creatorYear = AcademicYear::factory()->active()->create(['name' => '2026/2027']);
        [$skuadBatch, $skuadClass] = $this->programContext($skuadYear, 'SKUAD', 'skuad-student-doc-phase-14');
        [$creatorBatch, $creatorClass] = $this->programContext($creatorYear, 'Konten Kreator', 'creator-student-doc-phase-14');
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Peserta Multi Dokumen']);
        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'class_id' => $skuadClass->id,
            'membership_status' => StudentMembershipStatus::Active,
        ]);
        $this->join($student, $skuadBatch, $skuadClass);
        $this->join($student, $creatorBatch, $creatorClass);

        DocumentResource::factory()->published(DocumentAudience::Students)->create([
            'academic_year_id' => $skuadYear->id,
            'program_batch_id' => $skuadBatch->id,
            'category' => DocumentCategory::Guide,
            'title' => 'Bacaan SKUAD Lama',
        ]);
        $creatorDocument = DocumentResource::factory()->published(DocumentAudience::Students)->create([
            'academic_year_id' => $creatorYear->id,
            'program_batch_id' => $creatorBatch->id,
            'category' => DocumentCategory::Guide,
            'title' => 'Bacaan Creator Aktif',
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->get(route('student.documents.index'))
            ->assertOk()
            ->assertSee('Bacaan Creator Aktif')
            ->assertDontSee('Bacaan SKUAD Lama');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->get(route('student.documents.show', $creatorDocument))
            ->assertOk();
    }

    public function test_activity_documentation_is_scoped_to_active_program_and_photo_stays_compressed(): void
    {
        Storage::fake('public');
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch] = $this->programContext($year, 'SKUAD', 'skuad-activity-doc-phase-14');
        [$creatorBatch] = $this->programContext($year, 'Konten Kreator', 'creator-activity-doc-phase-14');
        $skuadDocumentation = ActivityDocumentation::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'created_by' => $coach->id,
            'title' => 'Dokumentasi SKUAD',
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($coach)
            ->post(route('activity-documentations.store'), [
                'academic_year_id' => $year->id,
                'activity_date' => today()->toDateString(),
                'title' => 'Dokumentasi Creator',
                'description' => 'Praktik produksi konten kreator.',
                'photo' => UploadedFile::fake()->image('creator-large.jpg', 2200, 1500),
                'resource_url' => 'https://example.com/album-creator',
                'video_url' => 'https://youtube.com/watch?v=creatorphase14',
            ])
            ->assertRedirect();

        $creatorDocumentation = ActivityDocumentation::query()->where('title', 'Dokumentasi Creator')->firstOrFail();
        $this->assertSame($creatorBatch->id, $creatorDocumentation->program_batch_id);
        Storage::disk('public')->assertExists($creatorDocumentation->photo_path);
        $this->assertLessThanOrEqual(512000, Storage::disk('public')->size($creatorDocumentation->photo_path));

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($principal)
            ->get(route('activity-documentations.index'))
            ->assertOk()
            ->assertSee('Dokumentasi Creator')
            ->assertDontSee('Dokumentasi SKUAD');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($principal)
            ->get(route('activity-documentations.show', $skuadDocumentation))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($coach)
            ->get(route('activity-documentations.edit', $skuadDocumentation))
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
            ['slug' => 'rkdd-phase-14'],
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
}
