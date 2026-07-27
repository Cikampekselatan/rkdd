<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\ClassStudent;
use App\Models\LearningMaterial;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Models\StudentLearningProgress;
use App\Models\StudentOnboardingResponse;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_student_sees_only_their_real_identity_and_zero_state_modules(): void
    {
        $student = $this->student('Nadia Dashboard', 'Kelas 8A', ['design', 'photography']);
        $this->student('Siswa Lain', 'Kelas 9A', ['coding']);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard pribadi peserta')
            ->assertSee('Nadia Dashboard')
            ->assertSee('Kelas 8A')
            ->assertSee('0 dari 0 pertemuan')
            ->assertSee('Belum ada tugas aktif')
            ->assertSee('Belum ada nilai yang dipublikasikan')
            ->assertSee('Notifikasi, tidak ada notifikasi baru')
            ->assertSee('Navigasi ponsel peserta')
            ->assertDontSee('Siswa Lain')
            ->assertDontSee('Kelas 9A');
    }

    public function test_dashboard_renders_safe_empty_profile_state_for_an_active_student(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'status' => UserStatus::Active,
            'password' => null,
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Belum terhubung ke kelas')
            ->assertSee($student->email)
            ->assertSee('0%');
    }

    public function test_dashboard_uses_real_learning_progress_and_continue_learning_session(): void
    {
        $student = $this->student('Nadia Belajar', 'Kelas 8B', ['design']);
        $academicYearId = $student->studentProfile->schoolClass->academic_year_id;
        $module = LearningModule::factory()->create([
            'academic_year_id' => $academicYearId,
            'module_number' => 1,
            'slug' => 'fondasi-digital',
        ]);
        $completed = LearningSession::factory()->published()->create([
            'academic_year_id' => $academicYearId,
            'learning_module_id' => $module->id,
            'session_number' => 1,
            'slug' => 'pertemuan-1',
            'title' => 'Pertemuan Selesai',
        ]);
        $next = LearningSession::factory()->published()->create([
            'academic_year_id' => $academicYearId,
            'learning_module_id' => $module->id,
            'session_number' => 2,
            'slug' => 'pertemuan-2',
            'title' => 'Lanjut Etika Digital',
        ]);
        LearningMaterial::factory()->create(['learning_session_id' => $next->id]);
        StudentLearningProgress::factory()->create([
            'user_id' => $student->id,
            'learning_session_id' => $completed->id,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('1 dari 2 pertemuan')
            ->assertSee('Lanjut Etika Digital')
            ->assertSee(route('student.learning.show', $next));
    }

    public function test_guest_non_student_and_suspended_student_cannot_access_dashboard(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));

        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $this->actingAs($teacher)->get(route('student.dashboard'))->assertForbidden();

        $suspended = User::factory()->withRole(RoleSlug::Student)->create([
            'status' => UserStatus::Suspended,
            'password' => null,
        ]);
        $this->actingAs($suspended)->get(route('student.dashboard'))->assertForbidden();
    }

    private function student(string $name, string $className, array $interests): User
    {
        $academicYear = AcademicYear::factory()->active()->create();
        $schoolClass = SchoolClass::factory()->create([
            'academic_year_id' => $academicYear->id,
            'name' => $className,
        ]);
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'name' => $name,
            'status' => UserStatus::Active,
            'password' => null,
        ]);

        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'class_id' => $schoolClass->id,
            'membership_status' => StudentMembershipStatus::Active,
            'nickname' => 'Nadia',
        ]);
        StudentOnboardingResponse::factory()->create([
            'user_id' => $student->id,
            'interests' => $interests,
            'completed_at' => now(),
        ]);
        ClassStudent::query()->create([
            'academic_year_id' => $academicYear->id,
            'class_id' => $schoolClass->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);

        return $student->refresh();
    }
}
