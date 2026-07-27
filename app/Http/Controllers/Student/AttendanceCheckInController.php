<?php

namespace App\Http\Controllers\Student;

use App\Actions\Attendance\CheckInAttendance;
use App\Actions\Attendance\EnableAttendanceCheckIn;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceCheckInController extends Controller
{
    public function scan(): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        return view('student.attendance.scan');
    }

    public function show(AttendanceSession $attendanceSession, string $token, Request $request): View|RedirectResponse
    {
        $error = $this->validateForPreview($attendanceSession, $token, $request);

        $attendanceSession->load(['learningSession:id,session_number,title', 'schoolClass:id,name']);
        $record = $request->user()->hasRole(RoleSlug::Student)
            ? AttendanceRecord::query()
                ->where('attendance_session_id', $attendanceSession->id)
                ->where('user_id', $request->user()->id)
                ->first()
            : null;

        return view('student.attendance.check-in', [
            'attendanceSession' => $attendanceSession,
            'record' => $record,
            'token' => $token,
            'error' => $error,
            'canCheckIn' => $request->user()->hasRole(RoleSlug::Student) && ! $error,
        ]);
    }

    public function store(
        AttendanceSession $attendanceSession,
        string $token,
        Request $request,
        CheckInAttendance $checkIn,
    ): RedirectResponse {
        abort_unless($request->user()->hasRole(RoleSlug::Student), 403);
        $this->authorize('viewAny', AttendanceRecord::class);

        $record = $checkIn->execute($attendanceSession, $token, $request->user());

        return redirect()->route('student.attendance.check-in.show', [$attendanceSession, $token])
            ->with('success', 'Check-in berhasil. Waktu hadir kamu sudah tercatat.');
    }

    private function validateForPreview(AttendanceSession $session, string $token, Request $request): ?string
    {
        try {
            if (! $request->user()->hasRole(RoleSlug::Student)) {
                throw ValidationException::withMessages(['student' => 'QR presensi hanya untuk akun siswa. Jika Anda sedang memakai akun staff, silakan logout lalu masuk dengan akun siswa untuk check-in.']);
            }

            if (! hash_equals((string) $session->check_in_token_hash, EnableAttendanceCheckIn::hashToken($token))) {
                throw ValidationException::withMessages(['token' => 'Link presensi tidak valid. Minta QR terbaru kepada guru/coach.']);
            }

            if (! $session->hasActiveCheckIn()) {
                throw ValidationException::withMessages(['attendance_session' => 'QR presensi belum aktif, sudah kedaluwarsa, atau sesi sudah ditutup.']);
            }

            if ($request->user()->status !== UserStatus::Active) {
                throw ValidationException::withMessages(['student' => 'Akun siswa belum aktif untuk melakukan presensi.']);
            }

            $isActiveMember = ClassStudent::query()
                ->where('academic_year_id', $session->academic_year_id)
                ->where('class_id', $session->class_id)
                ->when($session->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('user_id', $request->user()->id)
                ->where('status', StudentMembershipStatus::Active->value)
                ->exists();

            if (! $isActiveMember) {
                throw ValidationException::withMessages(['student' => 'Presensi ini hanya untuk peserta aktif di kelompok program terkait.']);
            }
        } catch (ValidationException $exception) {
            return collect($exception->errors())->flatten()->first();
        }

        return null;
    }
}
