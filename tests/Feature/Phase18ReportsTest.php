<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\ImportantNoteStatus;
use App\Enums\LearningSessionStatus;
use App\Enums\RemedialStatus;
use App\Enums\ReportType;
use App\Enums\RoleSlug;
use App\Enums\SubmissionStatus;
use App\Enums\TeacherActivityStatus;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\DocumentResource;
use App\Models\Grade;
use App\Models\ImportantNote;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\PortfolioItem;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\StudentOnboardingResponse;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\TeacherActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase18ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_render_and_print_all_twelve_report_types(): void
    {
        [$year, , , $teacher] = $this->reportContext();
        foreach (ReportType::cases() as $type) {
            $this->actingAs($teacher)->get(route('reports.show', [$type->value, 'year' => $year->id]))->assertOk()->assertSee($type->label());
            $this->actingAs($teacher)->get(route('reports.print', [$type->value, 'year' => $year->id]))->assertOk()->assertSee('RKDD CIKAMPEK SELATAN');
        }
    }

    public function test_report_policy_exposes_only_authorized_catalog_and_types(): void
    {
        [$year] = $this->reportContext();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $student = User::factory()->withRole(RoleSlug::Student)->create();

        $this->actingAs($principal)->get(route('reports.index'))->assertOk()->assertSee(route('reports.show', 'attendance'), false)->assertDontSee(route('reports.show', 'students'), false);
        $this->actingAs($principal)->get(route('reports.show', ['documents', 'year' => $year->id]))->assertOk()->assertSee('Dokumen Laporan Aktif')->assertDontSee('Dokumen Privat Belum Terbit');
        $this->actingAs($principal)->get(route('reports.show', ['students', 'year' => $year->id]))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.show', ['students', 'year' => $year->id]))->assertOk();
        $this->actingAs($admin)->get(route('reports.show', ['teacher-logs', 'year' => $year->id]))->assertOk()->assertSee('Materi Laporan Pengajar');
        $this->actingAs($admin)->get(route('reports.show', ['attendance', 'year' => $year->id]))->assertOk();
        $this->actingAs($admin)->get(route('reports.print', ['notes', 'year' => $year->id]))->assertOk()->assertSee('Catatan laporan program');
        $this->actingAs($admin)->get(route('reports.show', ['grades', 'year' => $year->id]))->assertForbidden();
        $this->actingAs($coach)->get(route('reports.show', ['notes', 'year' => $year->id]))->assertOk();
        $this->actingAs($coach)->get(route('reports.show', ['portfolio', 'year' => $year->id]))->assertOk();
        $this->actingAs($coach)->get(route('reports.show', ['attendance', 'year' => $year->id]))->assertOk();
        $this->actingAs($student)->get(route('reports.index'))->assertForbidden();
    }

    public function test_year_class_semester_and_date_filters_prevent_cross_scope_leakage(): void
    {
        [$year, $class, $student, $teacher] = $this->reportContext();
        $otherYear = AcademicYear::factory()->create(['name' => '2032/2033', 'is_active' => false]);
        $otherClass = SchoolClass::factory()->create(['academic_year_id' => $otherYear->id]);
        $otherStudent = $this->student($otherYear, $otherClass, 'Siswa Tahun Lain');

        $this->actingAs($teacher)->get(route('reports.show', ['students', 'year' => $year->id, 'class' => $class->id]))->assertOk()->assertSee($student->name)->assertDontSee($otherStudent->name);
        $this->actingAs($teacher)->get(route('reports.show', ['students', 'year' => $year->id, 'class' => $otherClass->id]))->assertSessionHasErrors('class');
        $this->actingAs($teacher)->get(route('reports.show', ['assignments', 'year' => $year->id, 'semester' => 2]))->assertOk()->assertDontSee('Tugas Laporan Semester Satu');
        $this->actingAs($teacher)->get(route('reports.show', ['teacher-logs', 'year' => $year->id, 'date_from' => now()->addDay()->toDateString()]))->assertOk()->assertDontSee('Materi Laporan Pengajar');
    }

    public function test_attendance_matrix_print_is_available_for_all_staff_roles(): void
    {
        [$year, $class, $student] = $this->reportContext();
        foreach ([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Teacher, RoleSlug::Coach, RoleSlug::Principal] as $role) {
            $viewer = User::factory()->withRole($role)->create();
            $this->actingAs($viewer)
                ->get(route('reports.matrix', ['attendance', 'year' => $year->id, 'class' => $class->id]))
                ->assertOk()
                ->assertSee('Matriks Kehadiran Peserta')
                ->assertSee('Pertemuan Ke')
                ->assertSee($student->name)
                ->assertSee('T');
        }
    }

    public function test_attendance_report_includes_active_students_without_attendance_records(): void
    {
        [$year, $class, $student, $teacher] = $this->reportContext();
        $unrecordedStudent = $this->student($year, $class, 'Siswa Belum Tercatat');

        $this->actingAs($teacher)
            ->get(route('reports.show', ['attendance', 'year' => $year->id, 'class' => $class->id]))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($unrecordedStudent->name)
            ->assertSee('Belum tercatat');
    }

    public function test_grade_and_remedial_reports_never_expose_private_teacher_notes(): void
    {
        [$year, , $student, $teacher] = $this->reportContext();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        foreach ([$teacher, $principal] as $viewer) {
            $this->actingAs($viewer)->get(route('reports.show', ['grades', 'year' => $year->id]))->assertOk()->assertSee($student->name)->assertSee('68.00')->assertDontSee('RAHASIA INTERNAL GURU');
            $this->actingAs($viewer)->get(route('reports.print', ['remedial', 'year' => $year->id]))->assertOk()->assertSee('Perlu remedial')->assertDontSee('RAHASIA INTERNAL GURU');
        }
    }

    private function reportContext(): array
    {
        $year = AcademicYear::factory()->create(['is_active' => true, 'name' => '2031/2032']);
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => 'Kelas Laporan']);
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create(['name' => 'Pembina Laporan']);
        $student = $this->student($year, $class, 'Siswa Laporan Utama');
        $code = RegistrationCode::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'created_by' => $teacher->id]);
        StudentOnboardingResponse::factory()->create(['user_id' => $student->id, 'registration_code_id' => $code->id, 'current_step' => 4]);
        $module = LearningModule::factory()->create(['academic_year_id' => $year->id, 'module_number' => 1, 'title' => 'Modul Laporan', 'slug' => 'modul-laporan']);
        $session = LearningSession::factory()->create(['learning_module_id' => $module->id, 'academic_year_id' => $year->id, 'session_number' => 1, 'semester' => 1, 'title' => 'Pertemuan Laporan', 'slug' => '1-pertemuan-laporan', 'status' => LearningSessionStatus::Completed, 'scheduled_at' => now(), 'published_at' => now()]);
        $attendance = AttendanceSession::factory()->closed()->create(['learning_session_id' => $session->id, 'academic_year_id' => $year->id, 'class_id' => $class->id]);
        AttendanceRecord::factory()->create(['attendance_session_id' => $attendance->id, 'user_id' => $student->id, 'status' => AttendanceStatus::Late, 'notes' => 'Datang lima menit terlambat']);
        TeacherActivityLog::factory()->create(['academic_year_id' => $year->id, 'teacher_id' => $teacher->id, 'activity_date' => today(), 'material' => 'Materi Laporan Pengajar', 'status' => TeacherActivityStatus::Verified, 'verified_at' => now()]);
        $assignment = Assignment::factory()->create(['learning_session_id' => $session->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'title' => 'Tugas Laporan Semester Satu', 'due_at' => now()->subDay()]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id, 'status' => SubmissionStatus::Late, 'submitted_at' => now()]);
        Grade::factory()->create(['submission_id' => $submission->id, 'total_score' => 68, 'achievement_level' => 2, 'private_note' => 'RAHASIA INTERNAL GURU', 'is_published' => true, 'published_at' => now(), 'remedial_status' => RemedialStatus::Assigned, 'remedial_due_at' => now()->addWeek()]);
        PortfolioItem::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'user_id' => $student->id, 'title' => 'Portofolio Laporan']);
        ImportantNote::factory()->create(['academic_year_id' => $year->id, 'created_by' => $teacher->id, 'note' => 'Catatan laporan program', 'status' => ImportantNoteStatus::Verified, 'verified_at' => now()]);
        DocumentResource::factory()->published()->create(['academic_year_id' => $year->id, 'title' => 'Dokumen Laporan Aktif', 'semester' => 1]);
        DocumentResource::factory()->create(['academic_year_id' => $year->id, 'title' => 'Dokumen Privat Belum Terbit', 'semester' => 1]);

        return [$year, $class, $student, $teacher];
    }

    private function student(AcademicYear $year, SchoolClass $class, string $name): User
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => $name]);
        StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id]);
        ClassStudent::create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'user_id' => $student->id, 'joined_at' => now(), 'status' => 'active']);

        return $student;
    }
}
