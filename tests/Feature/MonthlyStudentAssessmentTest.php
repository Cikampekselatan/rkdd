<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\ClassStudent;
use App\Models\MonthlyStudentAssessment;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyStudentAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_monthly_assessment_using_pdf_weight_composition(): void
    {
        [$teacher, $year, $class, $student] = $this->context();

        $this->actingAs($teacher)->get(route('teacher.monthly-assessments.index', [
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'semester' => 1,
        ]))->assertOk()
            ->assertSee('Asesmen bulanan siswa')
            ->assertSee('Produk dan portofolio')
            ->assertSee('35%')
            ->assertSee('Etika, sumber, keamanan, refleksi')
            ->assertSee('Export CSV')
            ->assertSee('Cetak / PDF');

        $this->actingAs($teacher)->get(route('teacher.monthly-assessments.create', [
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'semester' => 1,
        ]))->assertOk()
            ->assertSee($student->name)
            ->assertSee('Produk dan bukti');

        $this->actingAs($teacher)->post(route('teacher.monthly-assessments.store'), $this->payload($year, $class, $student))
            ->assertRedirect(route('teacher.monthly-assessments.index', [
                'academic_year_id' => $year->id,
                'class_id' => $class->id,
                'semester' => 1,
            ]));

        $assessment = MonthlyStudentAssessment::query()->firstOrFail();
        $this->assertSame($student->id, $assessment->user_id);
        $this->assertSame('Juli 2026', $assessment->period_label);
        $this->assertSame('84.05', (string) $assessment->final_score);
        $this->assertSame(3, $assessment->achievement_level);
        $this->assertTrue($assessment->is_published);
        $this->assertNotNull($assessment->published_at);
    }

    public function test_teacher_can_export_monthly_assessments_as_csv_and_premium_pdf_view(): void
    {
        [$teacher, $year, $class, $student] = $this->context();
        $this->actingAs($teacher)->post(route('teacher.monthly-assessments.store'), $this->payload($year, $class, $student))->assertRedirect();

        $query = ['academic_year_id' => $year->id, 'class_id' => $class->id, 'semester' => 1];
        $csv = $this->actingAs($teacher)->get(route('teacher.monthly-assessments.export.csv', $query));
        $csv->assertOk()->assertDownload('asesmen-bulanan-2026-2027-semester-1.csv');
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('Nama Peserta', $csvContent);
        $this->assertStringContainsString('Siswa Asesmen Bulanan', $csvContent);

        $this->actingAs($teacher)->get(route('teacher.monthly-assessments.print', $query))
            ->assertOk()
            ->assertSee('Laporan Hasil Asesmen Peserta')
            ->assertSee('Cetak / Simpan PDF')
            ->assertSee('Siswa Asesmen Bulanan')
            ->assertSee('Terampil');
    }

    public function test_monthly_assessment_prevents_duplicate_period_and_wrong_class_student(): void
    {
        [$teacher, $year, $class, $student] = $this->context();
        $otherClass = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $outsider = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Siswa Luar Asesmen']);
        StudentProfile::factory()->create(['user_id' => $outsider->id, 'class_id' => $otherClass->id, 'membership_status' => StudentMembershipStatus::Active]);
        ClassStudent::query()->create([
            'academic_year_id' => $year->id,
            'class_id' => $otherClass->id,
            'user_id' => $outsider->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active,
        ]);

        $this->actingAs($teacher)->post(route('teacher.monthly-assessments.store'), $this->payload($year, $class, $student))
            ->assertRedirect();
        $this->actingAs($teacher)->post(route('teacher.monthly-assessments.store'), $this->payload($year, $class, $student))
            ->assertSessionHasErrors('assessment_month');
        $this->actingAs($teacher)->post(route('teacher.monthly-assessments.store'), $this->payload($year, $class, $outsider))
            ->assertSessionHasErrors('user_id');
    }

    public function test_non_teacher_cannot_manage_monthly_assessments(): void
    {
        [$teacher, $year, $class, $student] = $this->context();
        $studentUser = User::factory()->withRole(RoleSlug::Student)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($studentUser)->get(route('teacher.monthly-assessments.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('teacher.monthly-assessments.store'), $this->payload($year, $class, $student))->assertForbidden();
        $this->actingAs($teacher)->post(route('teacher.monthly-assessments.store'), $this->payload($year, $class, $student))->assertRedirect();
        $this->assertSame(1, MonthlyStudentAssessment::query()->count());
    }

    public function test_student_sees_published_monthly_assessment_on_dashboard_and_grades(): void
    {
        [$teacher, $year, $class, $student] = $this->context();

        $this->actingAs($teacher)->post(route('teacher.monthly-assessments.store'), $this->payload($year, $class, $student))
            ->assertRedirect();

        $assessment = MonthlyStudentAssessment::query()->firstOrFail();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Asesmen bulanan')
            ->assertSee('Juli 2026')
            ->assertSee('Perlu memperkuat presentasi lisan.');

        $this->actingAs($student)->get(route('student.grades.index'))
            ->assertOk()
            ->assertSee('Asesmen bulanan')
            ->assertSee('Juli 2026')
            ->assertSee('Konsisten menyimpan bukti proses.');

        $this->actingAs($student)->get(route('student.grades.monthly.show', $assessment))
            ->assertOk()
            ->assertSee('Produk dan portofolio')
            ->assertSee('Bobot 35%')
            ->assertSee('Buka bukti karya')
            ->assertSee('Konsisten menyimpan bukti proses.')
            ->assertSee('Perlu memperkuat presentasi lisan.')
            ->assertDontSee('Catatan privat guru');
    }

    public function test_student_cannot_view_draft_or_other_student_monthly_assessment(): void
    {
        [$teacher, $year, $class, $student] = $this->context();
        $otherStudent = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Siswa Pembanding']);
        StudentProfile::factory()->create(['user_id' => $otherStudent->id, 'class_id' => $class->id, 'membership_status' => StudentMembershipStatus::Active]);
        ClassStudent::query()->create([
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'user_id' => $otherStudent->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active,
        ]);

        $draft = MonthlyStudentAssessment::query()->create(array_merge(
            $this->payload($year, $class, $student),
            [
                'assessment_month' => 2,
                'period_label' => 'Agustus 2026',
                'final_score' => 84.05,
                'achievement_level' => 3,
                'is_published' => false,
                'published_at' => null,
                'assessed_by' => $teacher->id,
                'assessed_at' => now(),
            ],
        ));
        $otherAssessment = MonthlyStudentAssessment::query()->create(array_merge(
            $this->payload($year, $class, $otherStudent),
            [
                'assessment_month' => 3,
                'period_label' => 'September 2026',
                'final_score' => 84.05,
                'achievement_level' => 3,
                'published_at' => now(),
                'assessed_by' => $teacher->id,
                'assessed_at' => now(),
            ],
        ));

        $this->actingAs($student)->get(route('student.grades.index'))
            ->assertOk()
            ->assertDontSee('Agustus 2026')
            ->assertDontSee('September 2026');

        $this->actingAs($student)->get(route('student.grades.monthly.show', $draft))->assertForbidden();
        $this->actingAs($student)->get(route('student.grades.monthly.show', $otherAssessment))->assertForbidden();
    }

    private function context(): array
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30']);
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => 'SKUAD 2026']);
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Siswa Asesmen Bulanan']);
        StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'membership_status' => StudentMembershipStatus::Active]);
        ClassStudent::query()->create([
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active,
        ]);

        return [$teacher, $year, $class, $student];
    }

    private function payload(AcademicYear $year, SchoolClass $class, User $student): array
    {
        return [
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'semester' => 1,
            'assessment_month' => 1,
            'product_summary' => 'Poster digital dan refleksi portofolio.',
            'evidence_url' => 'https://drive.google.com/example',
            'product_portfolio_score' => 80,
            'process_creativity_score' => 90,
            'collaboration_responsibility_score' => 85,
            'presentation_communication_score' => 80,
            'ethics_security_reflection_score' => 88,
            'strengths' => 'Konsisten menyimpan bukti proses.',
            'improvement_targets' => 'Perlu memperkuat presentasi lisan.',
            'remedial_plan' => 'Revisi caption dan sumber.',
            'enrichment_plan' => 'Menjadi mentor mini untuk teman kelompok.',
            'teacher_note' => 'Catatan privat guru.',
            'is_published' => 1,
        ];
    }
}
