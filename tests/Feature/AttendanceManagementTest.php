<?php

namespace Tests\Feature;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRecordLog;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_opens_session_with_default_records_and_cannot_duplicate_it(): void
    {
        [$teacher, $academicYear, $class, $learningSession, $students] = $this->attendanceContext();

        $response = $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
            'learning_session_id' => $learningSession->id,
            'class_id' => $class->id,
            'attendance_date' => today()->toDateString(),
            'notes' => 'Pertemuan rutin.',
        ]);

        $attendance = AttendanceSession::query()->firstOrFail();
        $response->assertRedirect(route('teacher.attendance.show', $attendance));
        $this->assertSame(AttendanceSessionStatus::Open, $attendance->status);
        $this->assertSame($academicYear->id, $attendance->academic_year_id);
        $this->assertSame($students->count(), $attendance->records()->count());
        $this->assertSame($students->count(), $attendance->records()->where('status', AttendanceStatus::Absent->value)->count());
        $this->assertTrue($attendance->check_in_enabled);
        $this->assertNotNull($attendance->check_in_token_hash);
        $this->assertNotNull($attendance->check_in_expires_at);

        $this->actingAs($teacher)->get(route('teacher.attendance.index'))
            ->assertOk()->assertSee('Presensi Peserta')->assertSee('Etika Digital');
        $this->actingAs($teacher)->get(route('teacher.attendance.show', $attendance))
            ->assertOk()->assertSee('QR presensi peserta')->assertSee('Export CSV')->assertSee('Cetak / PDF')->assertSee('Tandai semua:')->assertSee('Nadia Presensi');

        $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
            'learning_session_id' => $learningSession->id,
            'class_id' => $class->id,
            'attendance_date' => today()->toDateString(),
        ])->assertSessionHasErrors('learning_session_id');
        $this->assertSame(1, AttendanceSession::query()->count());
    }

    public function test_opening_rejects_mismatched_year_future_date_and_empty_class(): void
    {
        [$teacher, , , $learningSession] = $this->attendanceContext();
        $otherYear = AcademicYear::factory()->create();
        $emptyClass = SchoolClass::factory()->create(['academic_year_id' => $otherYear->id]);

        $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
            'learning_session_id' => $learningSession->id,
            'class_id' => $emptyClass->id,
            'attendance_date' => today()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['class_id', 'attendance_date']);
    }

    public function test_teacher_bulk_updates_closes_and_amends_with_audit_history(): void
    {
        [$teacher, , $class, $learningSession, $students] = $this->attendanceContext();
        $attendance = $this->open($teacher, $class, $learningSession);
        $first = $students[0];
        $second = $students[1];

        $this->actingAs($teacher)->put(route('teacher.attendance.update', $attendance), [
            'records' => [
                ['user_id' => $first->id, 'status' => AttendanceStatus::Late->value, 'notes' => 'Terlambat 10 menit'],
                ['user_id' => $second->id, 'status' => AttendanceStatus::Sick->value, 'notes' => 'Surat dari orang tua'],
            ],
        ])->assertSessionHas('success');

        $firstRecord = AttendanceRecord::query()->where('user_id', $first->id)->firstOrFail();
        $this->assertSame(AttendanceStatus::Late, $firstRecord->status);
        $this->assertDatabaseHas('attendance_record_logs', ['attendance_record_id' => $firstRecord->id, 'event' => 'bulk_updated']);

        $this->actingAs($teacher)->patch(route('teacher.attendance.close', $attendance))->assertSessionHas('success');
        $this->assertSame(AttendanceSessionStatus::Closed, $attendance->fresh()->status);
        $this->assertNotNull($attendance->fresh()->closed_at);

        $this->actingAs($teacher)->put(route('teacher.attendance.update', $attendance), [
            'records' => [
                ['user_id' => $first->id, 'status' => AttendanceStatus::Present->value],
                ['user_id' => $second->id, 'status' => AttendanceStatus::Present->value],
            ],
        ])->assertForbidden();

        $this->actingAs($teacher)->patch(route('teacher.attendance.records.amend', $firstRecord), [
            'status' => AttendanceStatus::Present->value,
            'notes' => 'Ternyata datang tepat waktu.',
            'reason' => 'Koreksi berdasarkan catatan pembina kelas.',
        ])->assertSessionHas('success');

        $this->assertSame(AttendanceStatus::Present, $firstRecord->fresh()->status);
        $log = AttendanceRecordLog::query()->where('attendance_record_id', $firstRecord->id)->latest('id')->firstOrFail();
        $this->assertSame('closed_amendment', $log->event);
        $this->assertSame('Koreksi berdasarkan catatan pembina kelas.', $log->reason);
        $this->assertSame(AttendanceStatus::Late, $log->old_status);
        $this->assertSame(AttendanceStatus::Present, $log->new_status);
    }

    public function test_student_checks_in_with_active_qr_and_teacher_can_still_override(): void
    {
        [$teacher, , $class, $learningSession, $students] = $this->attendanceContext();
        $attendance = $this->open($teacher, $class, $learningSession);
        $token = $attendance->fresh()->check_in_token_encrypted;
        $this->assertSame(AttendanceStatus::Absent, $attendance->records()->where('user_id', $students[0]->id)->firstOrFail()->status);

        $this->actingAs($students[0])->get(route('student.attendance.check-in.show', [$attendance, $token]))
            ->assertOk()
            ->assertSee('Check-in SKUAD')
            ->assertSee('Belum check-in untuk sesi ini.');

        $this->actingAs($students[0])->post(route('student.attendance.check-in.store', [$attendance, $token]))
            ->assertRedirect(route('student.attendance.check-in.show', [$attendance, $token]))
            ->assertSessionHas('success');

        $record = $attendance->records()->where('user_id', $students[0]->id)->firstOrFail();
        $this->assertNotNull($record->checked_in_at);
        $this->assertSame('qr', $record->check_in_method);
        $this->assertSame(AttendanceStatus::Present, $record->status);
        $this->assertDatabaseHas('attendance_record_logs', [
            'attendance_record_id' => $record->id,
            'user_id' => $students[0]->id,
            'event' => 'student_check_in',
        ]);

        $this->actingAs($students[0])->get(route('student.attendance.check-in.show', [$attendance, $token]))
            ->assertOk()
            ->assertSee('Sudah check-in');

        $this->actingAs($teacher)->put(route('teacher.attendance.update', $attendance), [
            'records' => [
                ['user_id' => $students[0]->id, 'status' => AttendanceStatus::Late->value, 'notes' => 'Scan setelah pembukaan'],
                ['user_id' => $students[1]->id, 'status' => AttendanceStatus::Present->value],
            ],
        ])->assertSessionHas('success');

        $this->assertSame(AttendanceStatus::Late, $record->fresh()->status);
        $this->assertSame('Scan setelah pembukaan', $record->fresh()->notes);
    }

    public function test_student_qr_check_in_creates_missing_attendance_record_for_active_member(): void
    {
        [$teacher, , $class, $learningSession, $students] = $this->attendanceContext();
        $attendance = $this->open($teacher, $class, $learningSession);
        $token = $attendance->fresh()->check_in_token_encrypted;

        $attendance->records()->where('user_id', $students[0]->id)->delete();
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $attendance->id,
            'user_id' => $students[0]->id,
        ]);

        $this->actingAs($students[0])->post(route('student.attendance.check-in.store', [$attendance, $token]))
            ->assertRedirect(route('student.attendance.check-in.show', [$attendance, $token]))
            ->assertSessionHas('success');

        $record = $attendance->records()->where('user_id', $students[0]->id)->firstOrFail();
        $this->assertSame(AttendanceStatus::Present, $record->status);
        $this->assertSame('qr', $record->check_in_method);
        $this->assertNotNull($record->checked_in_at);
    }

    public function test_staff_opening_student_qr_gets_clear_guidance_and_attendance_can_be_exported(): void
    {
        [$teacher, , $class, $learningSession, $students] = $this->attendanceContext();
        $attendance = $this->open($teacher, $class, $learningSession);
        $token = $attendance->fresh()->check_in_token_encrypted;

        $this->actingAs($teacher)->get(route('student.attendance.check-in.show', [$attendance, $token]))
            ->assertOk()
            ->assertSee('QR presensi hanya untuk akun siswa')
            ->assertDontSee('Check-in sekarang');

        $csv = $this->actingAs($teacher)->get(route('teacher.attendance.export.csv', $attendance));
        $csv
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Nama Peserta', $csv->streamedContent());
        $this->assertStringContainsString($students[0]->name, $csv->streamedContent());

        $this->actingAs($teacher)->get(route('teacher.attendance.print', $attendance))
            ->assertOk()
            ->assertSee('Daftar Hadir Peserta')
            ->assertSee($students[0]->name)
            ->assertSee('Cetak / Simpan PDF');
    }

    public function test_qr_check_in_rejects_wrong_class_expired_token_and_closed_session(): void
    {
        [$teacher, $academicYear, $class, $learningSession, $students] = $this->attendanceContext();
        $attendance = $this->open($teacher, $class, $learningSession);
        $token = $attendance->fresh()->check_in_token_encrypted;
        $otherClass = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Kelas 9B']);
        $outsider = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Siswa Luar Presensi']);
        StudentProfile::factory()->create([
            'user_id' => $outsider->id,
            'class_id' => $otherClass->id,
            'membership_status' => StudentMembershipStatus::Active,
        ]);
        ClassStudent::query()->create([
            'academic_year_id' => $academicYear->id,
            'class_id' => $otherClass->id,
            'user_id' => $outsider->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active,
        ]);

        $this->actingAs($outsider)->post(route('student.attendance.check-in.store', [$attendance, $token]))
            ->assertSessionHasErrors('student');

        $attendance->update(['check_in_expires_at' => now()->subMinute()]);

        $this->actingAs($students[0])->post(route('student.attendance.check-in.store', [$attendance, $token]))
            ->assertSessionHasErrors('attendance_session');
        $this->assertNull($attendance->records()->where('user_id', $students[0]->id)->firstOrFail()->checked_in_at);

        $attendance->update([
            'check_in_expires_at' => now()->addMinutes(30),
            'status' => AttendanceSessionStatus::Closed,
            'closed_by' => $teacher->id,
            'closed_at' => now(),
        ]);

        $this->actingAs($students[0])->post(route('student.attendance.check-in.store', [$attendance, $token]))
            ->assertSessionHasErrors('attendance_session');
    }

    public function test_bulk_input_must_contain_exactly_active_students_from_the_session_class(): void
    {
        [$teacher, , $class, $learningSession, $students] = $this->attendanceContext();
        $attendance = $this->open($teacher, $class, $learningSession);
        $outsider = User::factory()->withRole(RoleSlug::Student)->create();

        $this->actingAs($teacher)->put(route('teacher.attendance.update', $attendance), [
            'records' => [
                ['user_id' => $students[0]->id, 'status' => AttendanceStatus::Present->value],
                ['user_id' => $outsider->id, 'status' => AttendanceStatus::Absent->value],
            ],
        ])->assertSessionHasErrors('records');

        $this->assertSame(AttendanceStatus::Absent, $attendance->records()->where('user_id', $students[0]->id)->firstOrFail()->status);
        $this->assertDatabaseMissing('attendance_records', ['attendance_session_id' => $attendance->id, 'user_id' => $outsider->id]);
    }

    public function test_student_sees_only_personal_closed_history_and_real_percentage(): void
    {
        [$teacher, , $class, $learningSession, $students] = $this->attendanceContext();
        $attendance = $this->open($teacher, $class, $learningSession);
        $firstRecord = $attendance->records()->where('user_id', $students[0]->id)->firstOrFail();
        $secondRecord = $attendance->records()->where('user_id', $students[1]->id)->firstOrFail();
        $firstRecord->update(['status' => AttendanceStatus::Late, 'notes' => 'Datang setelah pembukaan']);
        $secondRecord->update(['status' => AttendanceStatus::Absent, 'notes' => 'Tanpa keterangan']);
        $attendance->update(['status' => AttendanceSessionStatus::Closed, 'closed_at' => now(), 'closed_by' => $teacher->id]);

        $this->actingAs($students[0])->get(route('student.attendance.index'))
            ->assertOk()
            ->assertSee('Kehadiran saya')
            ->assertSee('100%')
            ->assertSee('Datang setelah pembukaan')
            ->assertDontSee('Tanpa keterangan')
            ->assertDontSee($students[1]->name);

        $this->actingAs($students[1])->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('0%')
            ->assertSee('1 catatan kehadiran terbaru tersedia.');
    }

    public function test_open_session_is_hidden_from_student_and_non_teacher_cannot_manage_attendance(): void
    {
        [$teacher, , $class, $learningSession, $students] = $this->attendanceContext();
        $attendance = $this->open($teacher, $class, $learningSession);
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();

        $this->actingAs($students[0])->get(route('student.attendance.index'))
            ->assertOk()->assertSee('Buka scanner')->assertSee('Belum ada riwayat kehadiran');
        $this->actingAs($students[0])->get(route('student.attendance.scan'))
            ->assertOk()->assertSee('Scan QR dari layar guru')->assertSee('Mulai scan');
        $this->actingAs($students[0])->get(route('teacher.attendance.show', $attendance))->assertForbidden();
        $this->actingAs($admin)->get(route('teacher.attendance.index'))->assertForbidden();
        $this->actingAs($coach)->get(route('teacher.attendance.show', $attendance))->assertOk()->assertSee('QR presensi peserta');

        $suspended = User::factory()->suspended()->withRole(RoleSlug::Student)->create();
        $this->actingAs($suspended)->get(route('student.attendance.index'))->assertForbidden();
    }

    /** @return array{User, AcademicYear, SchoolClass, LearningSession, Collection<int, User>} */
    private function attendanceContext(): array
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $academicYear = AcademicYear::factory()->active()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Kelas 8A']);
        $module = LearningModule::factory()->create(['academic_year_id' => $academicYear->id, 'module_number' => 1]);
        $learningSession = LearningSession::factory()->published()->create([
            'academic_year_id' => $academicYear->id,
            'learning_module_id' => $module->id,
            'session_number' => 1,
            'title' => 'Etika Digital',
        ]);
        $students = collect(['Nadia Presensi', 'Raka Presensi'])->map(function (string $name) use ($academicYear, $class): User {
            $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => $name, 'password' => null]);
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'class_id' => $class->id,
                'membership_status' => StudentMembershipStatus::Active,
            ]);
            ClassStudent::query()->create([
                'academic_year_id' => $academicYear->id,
                'class_id' => $class->id,
                'user_id' => $student->id,
                'joined_at' => now(),
                'status' => StudentMembershipStatus::Active,
            ]);

            return $student->refresh();
        });

        return [$teacher, $academicYear, $class, $learningSession, $students];
    }

    private function open(User $teacher, SchoolClass $class, LearningSession $learningSession): AttendanceSession
    {
        $this->actingAs($teacher)->post(route('teacher.attendance.store'), [
            'learning_session_id' => $learningSession->id,
            'class_id' => $class->id,
            'attendance_date' => today()->toDateString(),
        ])->assertRedirect();

        return AttendanceSession::query()->firstOrFail();
    }
}
