<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInAttendance
{
    public function execute(AttendanceSession $session, string $token, User $student): AttendanceRecord
    {
        return DB::transaction(function () use ($session, $student, $token): AttendanceRecord {
            $session = AttendanceSession::query()->lockForUpdate()->findOrFail($session->id);

            $this->validateSession($session, $token);
            $this->validateStudent($session, $student);

            $record = AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($record?->checked_in_at) {
                return $record;
            }

            $oldStatus = $record?->status;
            $oldNotes = $record?->notes;
            $newStatus = $record?->status === AttendanceStatus::Absent || ! $record
                ? AttendanceStatus::Present
                : $record->status;

            $record ??= new AttendanceRecord([
                'attendance_session_id' => $session->id,
                'user_id' => $student->id,
            ]);

            $record->fill([
                'status' => $newStatus,
                'recorded_by' => $student->id,
                'recorded_at' => now(),
                'checked_in_at' => now(),
                'check_in_method' => 'qr',
            ])->save();

            $record->logs()->create([
                'user_id' => $student->id,
                'event' => 'student_check_in',
                'old_status' => $oldStatus,
                'new_status' => $record->status,
                'old_notes' => $oldNotes,
                'new_notes' => $record->notes,
                'reason' => 'Check-in mandiri siswa melalui QR/link presensi.',
            ]);

            return $record->refresh();
        });
    }

    private function validateSession(AttendanceSession $session, string $token): void
    {
        if (! hash_equals((string) $session->check_in_token_hash, EnableAttendanceCheckIn::hashToken($token))) {
            throw ValidationException::withMessages(['token' => 'Link presensi tidak valid. Minta QR terbaru kepada guru/coach.']);
        }

        if (! $session->isOpen() || ! $session->check_in_enabled) {
            throw ValidationException::withMessages(['attendance_session' => 'Sesi presensi belum aktif atau sudah ditutup.']);
        }

        if ($session->check_in_opens_at && $session->check_in_opens_at->isFuture()) {
            throw ValidationException::withMessages(['attendance_session' => 'QR presensi belum mulai berlaku.']);
        }

        if ($session->check_in_expires_at && $session->check_in_expires_at->isPast()) {
            throw ValidationException::withMessages(['attendance_session' => 'QR presensi sudah kedaluwarsa. Minta guru/coach memperbarui QR.']);
        }
    }

    private function validateStudent(AttendanceSession $session, User $student): void
    {
        if ($student->status !== UserStatus::Active) {
            throw ValidationException::withMessages(['student' => 'Akun siswa belum aktif untuk melakukan presensi.']);
        }

        $isActiveMember = ClassStudent::query()
            ->where('academic_year_id', $session->academic_year_id)
            ->where('class_id', $session->class_id)
            ->when($session->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('user_id', $student->id)
            ->where('status', StudentMembershipStatus::Active->value)
            ->exists();

        if (! $isActiveMember) {
            throw ValidationException::withMessages(['student' => 'Presensi ini hanya untuk peserta aktif pada kelompok program terkait.']);
        }
    }
}
