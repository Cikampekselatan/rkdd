<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\ImportantNoteStatus;
use App\Enums\LearningSessionStatus;
use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TeacherActivityStatus;
use App\Enums\UserStatus;
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
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\TeacherActivityLog;
use App\Models\User;
use App\Services\PrincipalDashboardService;
use App\Services\TeacherDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase17LeadershipDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_uses_real_active_year_metrics_and_attention_indicators(): void
    {
        [$year, $class, $student, $teacher, $session] = $this->dashboardContext();
        DB::enableQueryLog();
        $service = app(TeacherDashboardService::class)->build($year->id);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1, $service['kpis']['active_students']);
        $this->assertSame(1, $service['kpis']['new_registrations']);
        $this->assertSame(1, $service['kpis']['onboarding']);
        $this->assertSame(1, $service['kpis']['sessions_completed']);
        $this->assertSame(1, $service['kpis']['ungraded']);
        $this->assertSame(1, $service['kpis']['revisions']);
        $this->assertSame(50, $service['kpis']['attendance_rate']);
        $this->assertSame(1, $service['kpis']['open_notes']);
        $this->assertSame(1, $service['kpis']['pending_teacher_logs']);
        $this->assertSame($student->id, $service['attentionStudents']->first()['student']->id);
        $this->assertContains('Kehadiran 50%', $service['attentionStudents']->first()['reasons']);
        $this->assertSame(1, $service['charts']['grades']['<70']);
        $this->assertLessThanOrEqual(30, $queryCount);

        $this->actingAs($teacher)->get(route('teacher.dashboard', ['year' => $year->id]))->assertOk()->assertSee('Peserta perlu perhatian')->assertSee($student->name)->assertSee('Kehadiran 50%')->assertSee('Progress pertemuan program');
    }

    public function test_principal_dashboard_is_read_only_summary_and_excludes_other_year_data(): void
    {
        [$year] = $this->dashboardContext();
        $otherYear = AcademicYear::factory()->create(['is_active' => false]);
        ImportantNote::factory()->count(3)->create(['academic_year_id' => $otherYear->id, 'status' => ImportantNoteStatus::Open]);
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $data = app(PrincipalDashboardService::class)->build($year->id);

        $this->assertSame(1, $data['summary']['sessions']);
        $this->assertSame(50, $data['summary']['attendance']);
        $this->assertSame(65, $data['summary']['average_grade']);
        $this->assertSame(0, $data['summary']['teacher_logs']);
        $this->assertSame(1, $data['summary']['open_notes']);
        $this->assertSame(1, $data['summary']['documents']);
        $this->actingAs($principal)->get(route('principal.dashboard', ['year' => $year->id]))->assertOk()->assertSee('Monitoring program RKDD')->assertSee('Rata-rata nilai')->assertSee('Dokumen terbaru');
    }

    public function test_dashboard_year_filter_and_role_boundaries_are_enforced(): void
    {
        [$year, , $student, $teacher] = $this->dashboardContext();
        $otherYear = AcademicYear::factory()->create(['is_active' => false, 'name' => '2030/2031']);
        $otherSession = $this->sessionFor($otherYear, LearningSessionStatus::Completed);
        $this->assertNotNull($otherSession);
        $this->actingAs($teacher)->get(route('teacher.dashboard', ['year' => $otherYear->id]))->assertOk()->assertSee('2030/2031')->assertSee('1 / 1');
        $this->actingAs($teacher)->get(route('teacher.dashboard', ['year' => 999999]))->assertSessionHasErrors('year');
        $this->actingAs($student)->get(route('teacher.dashboard', ['year' => $year->id]))->assertForbidden();
        $this->actingAs($student)->get(route('principal.dashboard', ['year' => $year->id]))->assertForbidden();
        $this->actingAs($teacher)->get(route('principal.dashboard', ['year' => $year->id]))->assertForbidden();
    }

    public function test_leadership_dashboards_render_safe_zero_state_without_academic_year(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk()->assertSee('Periode belum tersedia');
        $this->actingAs($principal)->get(route('principal.dashboard'))->assertOk()->assertSee('Periode belum tersedia');
        $this->actingAs($coach)->get(route('coach.dashboard'))->assertOk()->assertSee('Periode belum tersedia');
    }

    public function test_coach_dashboard_uses_same_pembinaan_workspace_as_teacher(): void
    {
        [$year, $class, $student] = $this->dashboardContext();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        PortfolioItem::factory()->create([
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'title' => 'Proyek Terpantau Coach',
            'visibility' => PortfolioVisibility::School,
            'approval_status' => PortfolioApprovalStatus::Approved,
        ]);

        DB::enableQueryLog();
        $data = app(TeacherDashboardService::class)->build($year->id);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1, $data['kpis']['active_students']);
        $this->assertSame(1, $data['kpis']['open_notes']);
        $this->assertSame(1, $data['kpis']['pending_teacher_logs']);
        $this->assertLessThanOrEqual(35, $queryCount);
        $this->actingAs($coach)
            ->get(route('coach.dashboard', ['year' => $year->id]))
            ->assertOk()
            ->assertSee('Pusat kendali pembinaan')
            ->assertSee('Peserta perlu perhatian')
            ->assertSee($student->name)
            ->assertDontSee('Route placeholder');
    }

    private function dashboardContext(): array
    {
        $year = AcademicYear::factory()->create(['is_active' => true, 'name' => '2029/2030']);
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => 'Kelas 8 Fokus']);
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create(['name' => 'Pembina Data']);
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Siswa Perlu Dukungan']);
        StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'membership_status' => StudentMembershipStatus::Active]);
        ClassStudent::create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'user_id' => $student->id, 'joined_at' => now(), 'status' => 'active']);
        $onboardingUser = User::factory()->withRole(RoleSlug::Student)->create(['status' => UserStatus::Onboarding]);
        StudentProfile::factory()->create(['user_id' => $onboardingUser->id, 'class_id' => $class->id, 'membership_status' => StudentMembershipStatus::Onboarding]);
        $session = $this->sessionFor($year, LearningSessionStatus::Completed);
        $attendance = AttendanceSession::factory()->closed()->create(['learning_session_id' => $session->id, 'academic_year_id' => $year->id, 'class_id' => $class->id]);
        AttendanceRecord::factory()->create(['attendance_session_id' => $attendance->id, 'user_id' => $student->id, 'status' => AttendanceStatus::Present]);
        $secondSession = $this->sessionFor($year, LearningSessionStatus::Published);
        $secondAttendance = AttendanceSession::factory()->closed()->create(['learning_session_id' => $secondSession->id, 'academic_year_id' => $year->id, 'class_id' => $class->id]);
        AttendanceRecord::factory()->create(['attendance_session_id' => $secondAttendance->id, 'user_id' => $student->id, 'status' => AttendanceStatus::Absent]);

        $ungradedAssignment = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id, 'due_at' => now()->subDay()]);
        Submission::factory()->create(['assignment_id' => $ungradedAssignment->id, 'user_id' => $student->id, 'status' => SubmissionStatus::Submitted]);
        $revisionAssignment = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id, 'due_at' => now()->subDay()]);
        Submission::factory()->create(['assignment_id' => $revisionAssignment->id, 'user_id' => $student->id, 'status' => SubmissionStatus::RevisionRequested]);
        $gradedAssignment = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id, 'due_at' => now()->subDay()]);
        $gradedSubmission = Submission::factory()->create(['assignment_id' => $gradedAssignment->id, 'user_id' => $student->id, 'status' => SubmissionStatus::Graded]);
        Grade::factory()->create(['submission_id' => $gradedSubmission->id, 'total_score' => 65, 'achievement_level' => 2, 'is_published' => true, 'published_at' => now()]);
        ImportantNote::factory()->create(['academic_year_id' => $year->id, 'created_by' => $teacher->id, 'status' => ImportantNoteStatus::Open]);
        TeacherActivityLog::factory()->create(['academic_year_id' => $year->id, 'teacher_id' => $teacher->id, 'status' => TeacherActivityStatus::Submitted, 'submitted_at' => now()]);
        DocumentResource::factory()->published()->create(['academic_year_id' => $year->id, 'title' => 'Dokumen Monitoring Aktif']);

        return [$year, $class, $student, $teacher, $session];
    }

    private function sessionFor(AcademicYear $year, LearningSessionStatus $status): LearningSession
    {
        $number = LearningModule::withTrashed()->where('academic_year_id', $year->id)->count() + 1;
        $module = LearningModule::factory()->create(['academic_year_id' => $year->id, 'module_number' => $number, 'title' => 'Modul Dashboard '.$number, 'slug' => 'modul-dashboard-'.$number, 'sort_order' => $number]);
        $sessionNumber = LearningSession::withTrashed()->where('academic_year_id', $year->id)->count() + 1;

        return LearningSession::factory()->create(['learning_module_id' => $module->id, 'academic_year_id' => $year->id, 'session_number' => $sessionNumber, 'title' => 'Pertemuan Dashboard '.$sessionNumber, 'slug' => $sessionNumber.'-pertemuan-dashboard', 'status' => $status, 'published_at' => now()]);
    }
}
