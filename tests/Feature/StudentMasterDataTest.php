<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\StudentExitReason;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\AuthenticationLog;
use App\Models\ClassStudent;
use App\Models\SchoolClass;
use App\Models\StudentLearningProgress;
use App\Models\StudentOnboardingResponse;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_search_filter_and_view_student_detail_without_management_actions(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $academicYear = AcademicYear::factory()->active()->create();
        $designClass = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Kelas Desain']);
        $codingClass = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Kelas Coding']);
        $nadia = $this->student('Nadia Kreatif', $designClass, ['design'], UserStatus::Active, true);
        $this->student('Raka Coding', $codingClass, ['coding'], UserStatus::Suspended, true);
        $this->student('Siswa Onboarding', $designClass, ['design'], UserStatus::Onboarding, false);

        $this->actingAs($teacher)
            ->get(route('admin.students.index', ['q' => 'Nadia']))
            ->assertOk()
            ->assertSee('Nadia Kreatif')
            ->assertDontSee('Raka Coding');

        $this->actingAs($teacher)
            ->get(route('admin.students.index', [
                'status' => UserStatus::Active->value,
                'class_id' => $designClass->id,
                'interest' => 'design',
                'onboarding' => 'complete',
            ]))
            ->assertOk()
            ->assertSee('Nadia Kreatif')
            ->assertDontSee('Siswa Onboarding');

        AuthenticationLog::query()->create([
            'user_id' => $nadia->id,
            'provider' => 'google',
            'event' => 'login_success',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.students.show', $nadia))
            ->assertOk()
            ->assertSee('Profil peserta 360')
            ->assertSee('Kelas Desain')
            ->assertSee('Login Success')
            ->assertDontSee('Arsipkan peserta');
    }

    public function test_admin_can_suspend_and_activate_a_student_with_consistent_memberships(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $schoolClass = SchoolClass::factory()->create();
        $student = $this->student('Nadia', $schoolClass, ['design'], UserStatus::Active, true);

        $this->actingAs($admin)
            ->patch(route('admin.students.suspend', $student))
            ->assertSessionHas('success');

        $this->assertSame(UserStatus::Suspended, $student->fresh()->status);
        $this->assertSame(StudentMembershipStatus::Suspended, $student->studentProfile->fresh()->membership_status);
        $this->assertDatabaseHas('class_students', [
            'user_id' => $student->id,
            'status' => StudentMembershipStatus::Suspended->value,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.students.activate', $student))
            ->assertSessionHas('success');

        $this->assertSame(UserStatus::Active, $student->fresh()->status);
        $this->assertSame(StudentMembershipStatus::Active, $student->studentProfile->fresh()->membership_status);
        $this->assertDatabaseHas('class_students', [
            'user_id' => $student->id,
            'status' => StudentMembershipStatus::Active->value,
        ]);
    }

    public function test_admin_can_deactivate_and_reactivate_membership_with_exit_history(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $student = $this->student('Siswa Kelas Sembilan', SchoolClass::factory()->create(), ['design'], UserStatus::Active, true);

        $this->actingAs($admin)
            ->patch(route('admin.students.deactivate', $student), [
                'exit_reason' => StudentExitReason::GradeNineSemesterTwo->value,
                'exit_notes' => 'Fokus persiapan kelulusan.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(UserStatus::Inactive, $student->fresh()->status);
        $this->assertSame(StudentMembershipStatus::Inactive, $student->studentProfile->fresh()->membership_status);
        $this->assertDatabaseHas('class_students', [
            'user_id' => $student->id,
            'status' => StudentMembershipStatus::Inactive->value,
            'exit_reason' => StudentExitReason::GradeNineSemesterTwo->value,
            'exit_notes' => 'Fokus persiapan kelulusan.',
        ]);
        $this->assertNotNull($student->classMemberships()->firstOrFail()->left_at);
        $this->actingAs($student->fresh())->get(route('student.dashboard'))->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.students.activate', $student))
            ->assertSessionHas('success');

        $this->assertSame(UserStatus::Active, $student->fresh()->status);
        $this->assertDatabaseHas('class_students', [
            'user_id' => $student->id,
            'status' => StudentMembershipStatus::Active->value,
            'left_at' => null,
            'exit_reason' => null,
        ]);
    }

    public function test_admin_can_soft_delete_a_student_and_archive_memberships(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $student = $this->student('Siswa Arsip', SchoolClass::factory()->create(), ['video'], UserStatus::Active, true);
        $profile = $student->studentProfile;

        $this->actingAs($admin)
            ->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'));

        $this->assertSoftDeleted($student);
        $this->assertSoftDeleted($profile);
        $this->assertSame(UserStatus::Archived, User::withTrashed()->findOrFail($student->id)->status);
        $this->assertDatabaseHas('class_students', [
            'user_id' => $student->id,
            'status' => StudentMembershipStatus::Archived->value,
        ]);
    }

    public function test_admin_can_reset_archived_test_student_back_to_registration_code(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $student = $this->student('Siswa Test Reset', SchoolClass::factory()->create(), ['video'], UserStatus::Active, true);

        $this->actingAs($admin)
            ->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'));

        $archived = User::withTrashed()->findOrFail($student->id);

        $this->actingAs($admin)
            ->patch(route('admin.students.reset-onboarding', $archived))
            ->assertRedirect(route('admin.students.show', $archived));

        $archived->refresh();
        $this->assertFalse($archived->trashed());
        $this->assertSame(UserStatus::Onboarding, $archived->status);
        $this->assertFalse($archived->fresh('roles')->hasRole(RoleSlug::Student));
        $this->assertDatabaseMissing('student_profiles', ['user_id' => $archived->id]);
        $this->assertDatabaseMissing('student_onboarding_responses', ['user_id' => $archived->id]);
        $this->assertDatabaseMissing('class_students', ['user_id' => $archived->id]);

        $this->actingAs($archived)
            ->get(route('onboarding.registration-code.show'))
            ->assertOk()
            ->assertSee('Masukkan kode pendaftaran');
    }

    public function test_admin_can_permanently_delete_empty_test_student_but_not_student_with_learning_history(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $emptyTest = $this->student('Siswa Test Hapus', SchoolClass::factory()->create(), ['design'], UserStatus::Onboarding, false);

        $this->actingAs($admin)
            ->delete(route('admin.students.purge-test', $emptyTest))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseMissing('users', ['id' => $emptyTest->id]);

        $studentWithHistory = $this->student('Siswa Ada Riwayat', SchoolClass::factory()->create(), ['coding'], UserStatus::Active, true);
        StudentLearningProgress::factory()->create(['user_id' => $studentWithHistory->id]);

        $this->actingAs($admin)
            ->delete(route('admin.students.destroy', $studentWithHistory))
            ->assertRedirect(route('admin.students.index'));

        $archivedWithHistory = User::withTrashed()->findOrFail($studentWithHistory->id);

        $this->actingAs($admin)
            ->delete(route('admin.students.purge-test', $archivedWithHistory))
            ->assertUnprocessable();

        $this->assertNotNull(User::withTrashed()->find($studentWithHistory->id));
    }

    public function test_student_cannot_view_master_list_and_teacher_cannot_change_status(): void
    {
        $studentActor = User::factory()->withRole(RoleSlug::Student)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $student = $this->student('Target', SchoolClass::factory()->create(), ['coding'], UserStatus::Active, true);

        $this->actingAs($studentActor)
            ->get(route('admin.students.index'))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->patch(route('admin.students.suspend', $student))
            ->assertForbidden();
    }

    public function test_incomplete_onboarding_student_cannot_be_activated(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $student = $this->student('Belum Lengkap', SchoolClass::factory()->create(), ['coding'], UserStatus::Suspended, false);

        $this->actingAs($admin)
            ->patch(route('admin.students.activate', $student))
            ->assertUnprocessable();

        $this->assertSame(UserStatus::Suspended, $student->fresh()->status);
    }

    /**
     * @param  list<string>  $interests
     */
    private function student(
        string $name,
        SchoolClass $schoolClass,
        array $interests,
        UserStatus $status,
        bool $completed,
    ): User {
        $user = User::factory()->withRole(RoleSlug::Student)->create([
            'name' => $name,
            'status' => $status,
            'password' => null,
        ]);
        StudentProfile::factory()->create([
            'user_id' => $user->id,
            'class_id' => $schoolClass->id,
            'joined_at' => $completed ? now() : null,
            'membership_status' => match ($status) {
                UserStatus::Active => StudentMembershipStatus::Active,
                UserStatus::Suspended => StudentMembershipStatus::Suspended,
                default => StudentMembershipStatus::Onboarding,
            },
        ]);
        StudentOnboardingResponse::factory()->create([
            'user_id' => $user->id,
            'interests' => $interests,
            'completed_at' => $completed ? now() : null,
        ]);
        ClassStudent::query()->create([
            'academic_year_id' => $schoolClass->academic_year_id,
            'class_id' => $schoolClass->id,
            'user_id' => $user->id,
            'joined_at' => now(),
            'status' => $status === UserStatus::Suspended
                ? StudentMembershipStatus::Suspended->value
                : StudentMembershipStatus::Active->value,
        ]);

        return $user->refresh();
    }
}
