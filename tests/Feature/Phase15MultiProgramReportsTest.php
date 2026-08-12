<?php

namespace Tests\Feature;

use App\Enums\ReportType;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\ActivityDocumentation;
use App\Models\ClassStudent;
use App\Models\Institution;
use App\Models\MonthlyStudentAssessment;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase15MultiProgramReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_reports_default_to_active_program_without_leaking_other_programs(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $skuadYear = AcademicYear::factory()->create(['name' => 'SKUAD 2026/2027', 'is_active' => false]);
        $creatorYear = AcademicYear::factory()->active()->create(['name' => 'Journi3 2026/2027']);
        [$skuadBatch, $skuadClass] = $this->programContext($skuadYear, 'SKUAD', 'skuad-report-phase-15', 'SKUAD 2026/2027');
        [$creatorBatch, $creatorClass] = $this->programContext($creatorYear, 'Konten Kreator', 'creator-report-phase-15', 'Journi3 2026/2027');
        $skuadStudent = $this->student($skuadYear, $skuadBatch, $skuadClass, 'Siswa Laporan SKUAD');
        $creatorStudent = $this->student($creatorYear, $creatorBatch, $creatorClass, 'Peserta Laporan Creator');

        $response = $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($superAdmin)
            ->get(route('reports.show', ['students']));

        $response->assertOk()
            ->assertSee($creatorStudent->name)
            ->assertDontSee($skuadStudent->name)
            ->assertDontSee('Semua program');

        preg_match('/<select class="form-select" id="year" name="year">(.*?)<\/select>/s', $response->getContent(), $yearSelect);
        $this->assertStringContainsString('Journi3 2026/2027', $yearSelect[1] ?? '');
        $this->assertStringNotContainsString('SKUAD 2026/2027', $yearSelect[1] ?? '');

        $this->actingAs($superAdmin)
            ->get(route('reports.show', ['students', 'year' => $creatorYear->id, 'program_batch_id' => $creatorBatch->id]))
            ->assertOk()
            ->assertSee($creatorStudent->name)
            ->assertDontSee($skuadStudent->name);
    }

    public function test_non_super_admin_reports_are_locked_to_active_program_and_csv_export(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-teacher-report-phase-15');
        [$creatorBatch, $creatorClass] = $this->programContext($year, 'Konten Kreator', 'creator-teacher-report-phase-15');
        $skuadStudent = $this->student($year, $skuadBatch, $skuadClass, 'Siswa SKUAD Terkunci');
        $creatorStudent = $this->student($year, $creatorBatch, $creatorClass, 'Peserta Creator Tampil');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('reports.show', ['students', 'year' => $year->id]))
            ->assertOk()
            ->assertSee($creatorStudent->name)
            ->assertDontSee($skuadStudent->name);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('reports.show', ['students', 'year' => $year->id, 'program_batch_id' => $skuadBatch->id]))
            ->assertSessionHasErrors('program_batch_id');

        $csv = $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('reports.export.csv', ['students', 'year' => $year->id]));

        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Peserta Creator Tampil', $csv->streamedContent());
        $this->assertStringNotContainsString('Siswa SKUAD Terkunci', $csv->streamedContent());
    }

    public function test_monthly_assessment_and_activity_documentation_reports_are_available_per_program(): void
    {
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$batch, $class] = $this->programContext($year, 'Jurnalis Digital', 'jurnalis-report-phase-15');
        $student = $this->student($year, $batch, $class, 'Peserta Asesmen Laporan');
        MonthlyStudentAssessment::query()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'semester' => 1,
            'assessment_month' => 1,
            'period_label' => 'Juli 2026',
            'product_summary' => 'Produk laporan.',
            'final_score' => 87,
            'achievement_level' => 3,
            'assessed_by' => $teacher->id,
            'assessed_at' => now(),
            'is_published' => true,
            'published_at' => now(),
        ]);
        ActivityDocumentation::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'created_by' => $teacher->id,
            'title' => 'Dokumentasi Liputan Desa',
            'video_url' => 'https://example.com/video-liputan',
        ]);

        $this->withSession(['active_program_batch_id' => $batch->id])
            ->actingAs($principal)
            ->get(route('reports.show', [ReportType::MonthlyAssessments->value, 'year' => $year->id]))
            ->assertOk()
            ->assertSee('Peserta Asesmen Laporan')
            ->assertSee('87.00');

        $this->withSession(['active_program_batch_id' => $batch->id])
            ->actingAs($principal)
            ->get(route('reports.show', [ReportType::ActivityDocumentations->value, 'year' => $year->id]))
            ->assertOk()
            ->assertSee('Dokumentasi Liputan Desa')
            ->assertSee('https://example.com/video-liputan');
    }

    /**
     * @return array{ProgramBatch, SchoolClass}
     */
    private function programContext(AcademicYear $year, string $name, string $slug, string $periodLabel = '2026'): array
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
            ['slug' => 'rkdd-phase-15'],
            ['name' => 'RKDD Cikampek Selatan', 'type' => 'rkdd', 'is_active' => true],
        );
        $batch = ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => $name.' 2026',
            'slug' => $slug.'-2026',
            'period_label' => $periodLabel,
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

    private function student(AcademicYear $year, ProgramBatch $batch, SchoolClass $class, string $name): User
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => $name]);
        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'class_id' => $class->id,
            'membership_status' => StudentMembershipStatus::Active,
        ]);
        ClassStudent::query()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);

        return $student;
    }
}
