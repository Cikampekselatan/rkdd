<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\ImportantNoteStatus;
use App\Enums\RemedialStatus;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TeacherActivityStatus;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\Grade;
use App\Models\ImportantNote;
use App\Models\Institution;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionVersion;
use App\Models\TeacherActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase16SecurityProgramBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_submission_grade_and_file_routes_reject_cross_program_access(): void
    {
        Storage::fake('local');
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-security-phase-16');
        [$creatorBatch] = $this->programContext($year, 'Konten Kreator', 'creator-security-phase-16');
        $student = $this->student($year, $skuadBatch, $skuadClass, 'Siswa File Privat');
        $assignment = Assignment::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'rubric_id' => $this->rubric($year)->id,
            'is_published' => true,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'status' => SubmissionStatus::Submitted,
        ]);
        $version = SubmissionVersion::query()->create([
            'submission_id' => $submission->id,
            'version_number' => 1,
            'text_content' => 'Jawaban privat siswa.',
            'submitted_at' => now(),
        ]);
        Storage::disk('local')->put('submissions/private.txt', 'konten privat');
        $file = SubmissionFile::query()->create([
            'submission_version_id' => $version->id,
            'original_name' => 'private.txt',
            'stored_path' => 'submissions/private.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 12,
        ]);
        $grade = Grade::factory()->create([
            'submission_id' => $submission->id,
            'is_published' => true,
            'published_at' => now(),
            'remedial_status' => RemedialStatus::Assigned,
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('teacher.submissions.show', $submission))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('submission-files.download', $file))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->patch(route('teacher.grades.remedial.complete', $grade))
            ->assertForbidden();
    }

    public function test_attendance_records_registration_codes_and_classes_reject_cross_program_actions(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-admin-security-phase-16');
        [$creatorBatch] = $this->programContext($year, 'Konten Kreator', 'creator-admin-security-phase-16');
        $student = $this->student($year, $skuadBatch, $skuadClass, 'Siswa Presensi Program');
        $module = LearningModule::factory()->create(['academic_year_id' => $year->id, 'program_batch_id' => $skuadBatch->id]);
        $session = LearningSession::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'learning_module_id' => $module->id,
        ]);
        $attendanceSession = AttendanceSession::factory()->closed()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'learning_session_id' => $session->id,
        ]);
        $record = AttendanceRecord::factory()->create([
            'attendance_session_id' => $attendanceSession->id,
            'user_id' => $student->id,
            'status' => AttendanceStatus::Present,
        ]);
        $code = RegistrationCode::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'created_by' => $admin->id,
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->patch(route('teacher.attendance.records.amend', $record), ['status' => AttendanceStatus::Permitted->value, 'notes' => 'Izin'])
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($admin)
            ->get(route('admin.registration-codes.edit', $code))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($admin)
            ->get(route('admin.classes.edit', $skuadClass))
            ->assertForbidden();
    }

    public function test_student_master_detail_and_status_actions_reject_cross_program_direct_access(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-student-security-phase-16');
        [$creatorBatch] = $this->programContext($year, 'Konten Kreator', 'creator-student-security-phase-16');
        $student = $this->student($year, $skuadBatch, $skuadClass, 'Siswa Master Program Lain');

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($admin)
            ->get(route('admin.students.show', $student))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($admin)
            ->patch(route('admin.students.suspend', $student))
            ->assertForbidden();
    }

    public function test_teacher_logs_and_important_notes_reject_cross_program_direct_access_and_signing(): void
    {
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch] = $this->programContext($year, 'SKUAD', 'skuad-log-security-phase-16');
        [$creatorBatch] = $this->programContext($year, 'Konten Kreator', 'creator-log-security-phase-16');
        $log = TeacherActivityLog::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'teacher_id' => $coach->id,
            'status' => TeacherActivityStatus::Submitted,
            'submitted_at' => now(),
        ]);
        $note = ImportantNote::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'created_by' => $coach->id,
            'updated_by' => $coach->id,
            'status' => ImportantNoteStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('activity-logs.show', $log))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->patch(route('activity-logs.review', $log), ['decision' => 'verified', 'reviewer_signature_drawn' => $this->drawnSignature()])
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($principal)
            ->get(route('important-notes.show', $note))
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
            ['slug' => 'rkdd-phase-16'],
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

    private function rubric(AcademicYear $year): Rubric
    {
        $rubric = Rubric::factory()->create(['academic_year_id' => $year->id]);
        $criterion = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kualitas karya', 'weight' => 100, 'sort_order' => 1]);
        foreach ([1, 2, 3, 4] as $level) {
            $criterion->levels()->create(['level' => $level, 'label' => 'Level '.$level, 'description' => 'Deskripsi '.$level]);
        }

        return $rubric;
    }

    private function drawnSignature(): string
    {
        return 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
    }
}
