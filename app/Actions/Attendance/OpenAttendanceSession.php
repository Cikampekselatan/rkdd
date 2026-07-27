<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Enums\StudentMembershipStatus;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OpenAttendanceSession
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, User $actor): AttendanceSession
    {
        return DB::transaction(function () use ($actor, $data): AttendanceSession {
            $learningSession = LearningSession::query()->lockForUpdate()->findOrFail($data['learning_session_id']);
            $schoolClass = SchoolClass::query()->findOrFail($data['class_id']);

            if ($learningSession->academic_year_id !== $schoolClass->academic_year_id) {
                throw ValidationException::withMessages(['class_id' => 'Pertemuan dan kelas harus berada pada tahun ajaran yang sama.']);
            }

            if (collect([$learningSession->program_batch_id, $schoolClass->program_batch_id])->filter()->unique()->count() > 1) {
                throw ValidationException::withMessages(['class_id' => 'Pertemuan dan kelas harus berasal dari program yang sama.']);
            }

            if (AttendanceSession::query()->where('learning_session_id', $learningSession->id)->where('class_id', $schoolClass->id)->exists()) {
                throw ValidationException::withMessages(['learning_session_id' => 'Sesi presensi untuk pertemuan dan kelas ini sudah tersedia.']);
            }

            $studentIds = ClassStudent::query()
                ->where('academic_year_id', $schoolClass->academic_year_id)
                ->where('class_id', $schoolClass->id)
                ->when($schoolClass->program_batch_id ?? $learningSession->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('status', StudentMembershipStatus::Active->value)
                ->orderBy('user_id')
                ->pluck('user_id');

            if ($studentIds->isEmpty()) {
                throw ValidationException::withMessages(['class_id' => 'Kelas belum memiliki siswa aktif untuk dipresensi.']);
            }

            $token = Str::random(64);
            $now = now();

            $attendance = AttendanceSession::query()->create([
                ...$data,
                'academic_year_id' => $learningSession->academic_year_id,
                'program_batch_id' => $schoolClass->program_batch_id ?? $learningSession->program_batch_id,
                'status' => AttendanceSessionStatus::Open,
                'check_in_token_encrypted' => $token,
                'check_in_token_hash' => EnableAttendanceCheckIn::hashToken($token),
                'check_in_opens_at' => $now,
                'check_in_expires_at' => $now->copy()->addMinutes(EnableAttendanceCheckIn::DEFAULT_MINUTES),
                'check_in_enabled' => true,
                'opened_by' => $actor->id,
                'opened_at' => $now,
            ]);

            $attendance->records()->insert($studentIds->map(fn (int $studentId): array => [
                'attendance_session_id' => $attendance->id,
                'user_id' => $studentId,
                'status' => AttendanceStatus::Present->value,
                'notes' => null,
                'recorded_by' => $actor->id,
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            return $attendance;
        });
    }
}
