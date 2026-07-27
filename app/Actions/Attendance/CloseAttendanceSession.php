<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceSessionStatus;
use App\Enums\StudentMembershipStatus;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseAttendanceSession
{
    public function execute(AttendanceSession $session, User $actor): AttendanceSession
    {
        return DB::transaction(function () use ($actor, $session): AttendanceSession {
            $session = AttendanceSession::query()->lockForUpdate()->findOrFail($session->id);

            if (! $session->isOpen()) {
                throw ValidationException::withMessages(['attendance_session' => 'Sesi presensi sudah ditutup.']);
            }

            $activeStudentIds = ClassStudent::query()
                ->where('academic_year_id', $session->academic_year_id)
                ->where('class_id', $session->class_id)
                ->where('status', StudentMembershipStatus::Active->value)
                ->pluck('user_id');
            $recordedStudentIds = $session->records()->pluck('user_id');

            if ($activeStudentIds->sort()->values()->all() !== $recordedStudentIds->sort()->values()->all()) {
                throw ValidationException::withMessages(['attendance_session' => 'Daftar siswa aktif berubah. Simpan ulang presensi sebelum menutup sesi.']);
            }

            $session->update([
                'status' => AttendanceSessionStatus::Closed,
                'check_in_enabled' => false,
                'closed_by' => $actor->id,
                'closed_at' => now(),
            ]);

            return $session->refresh();
        });
    }
}
