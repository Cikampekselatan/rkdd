<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Attendance\AmendClosedAttendanceRecord;
use App\Actions\Attendance\CloseAttendanceSession;
use App\Actions\Attendance\EnableAttendanceCheckIn;
use App\Actions\Attendance\OpenAttendanceSession;
use App\Actions\Attendance\SaveBulkAttendance;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AmendAttendanceRecordRequest;
use App\Http\Requests\Teacher\AttendanceIndexRequest;
use App\Http\Requests\Teacher\OpenAttendanceSessionRequest;
use App\Http\Requests\Teacher\SaveBulkAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Services\AttendanceSummaryService;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(AttendanceIndexRequest $request, AttendanceSummaryService $summaries): View
    {
        $filters = $request->validated();
        $programContext = app(ProgramContextService::class);
        $activeBatchId = $programContext->activeBatchId($request->user());
        $academicYears = $programContext->academicYears($request->user(), ['id', 'name', 'is_active']);
        $academicYearId = isset($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : $academicYears->first()?->id;
        $classes = SchoolClass::query()
            ->when($academicYearId, fn ($query, int $year) => $query->where('academic_year_id', $year))
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('is_active', true)
            ->orderBy('grade_level')->orderBy('name')->get(['id', 'academic_year_id', 'name']);
        $classId = isset($filters['class_id'])
            ? $classes->firstWhere('id', (int) $filters['class_id'])?->id
            : $classes->first()?->id;
        $sessions = AttendanceSession::query()
            ->with(['learningSession:id,session_number,title', 'schoolClass:id,name', 'records:id,attendance_session_id,status'])
            ->when($academicYearId, fn ($query, int $year) => $query->where('academic_year_id', $year))
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->when($classId, fn ($query, int $class) => $query->where('class_id', $class))
            ->orderByDesc('attendance_date')->paginate(12)->withQueryString();
        $learningSessions = LearningSession::query()
            ->when($academicYearId, fn ($query, int $year) => $query->where('academic_year_id', $year))
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->orderBy('session_number')->get(['id', 'academic_year_id', 'session_number', 'title']);

        return view('teacher.attendance.index', [
            'academicYears' => $academicYears,
            'classes' => $classes,
            'learningSessions' => $learningSessions,
            'sessions' => $sessions,
            'studentRecap' => $academicYearId && $classId ? $summaries->perStudent($academicYearId, $classId) : collect(),
            'academicYearId' => $academicYearId,
            'classId' => $classId,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    public function store(OpenAttendanceSessionRequest $request, OpenAttendanceSession $open): RedirectResponse
    {
        $attendance = $open->execute($request->validated(), $request->user());

        return redirect()->route('teacher.attendance.show', $attendance)
            ->with('success', 'Sesi presensi dibuka. Semua siswa aktif ditandai hadir sebagai nilai awal.');
    }

    public function show(AttendanceSession $attendanceSession, AttendanceSummaryService $summaries): View
    {
        $this->authorize('view', $attendanceSession);
        $this->loadAttendanceSession($attendanceSession);

        return view('teacher.attendance.show', [
            'attendanceSession' => $attendanceSession,
            'summary' => $summaries->forSession($attendanceSession),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    public function exportCsv(AttendanceSession $attendanceSession): StreamedResponse
    {
        $this->authorize('view', $attendanceSession);
        $this->loadAttendanceSession($attendanceSession);
        $filename = str('daftar-hadir-'.$attendanceSession->attendance_date->format('Y-m-d').'-'.$attendanceSession->schoolClass->name)->slug().'.csv';

        return response()->streamDownload(function () use ($attendanceSession): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', 'Nama Peserta', 'Email', 'Status', 'Check-in', 'Metode', 'Catatan']);
            foreach ($attendanceSession->records as $index => $record) {
                fputcsv($handle, [
                    $index + 1,
                    $record->student->name,
                    $record->student->email,
                    $record->status->label(),
                    $record->checked_in_at?->format('Y-m-d H:i:s') ?? '',
                    $record->check_in_method ?? '',
                    $record->notes ?? '',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(AttendanceSession $attendanceSession, AttendanceSummaryService $summaries): View
    {
        $this->authorize('view', $attendanceSession);
        $this->loadAttendanceSession($attendanceSession);

        return view('teacher.attendance.print', [
            'attendanceSession' => $attendanceSession,
            'summary' => $summaries->forSession($attendanceSession),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    public function update(SaveBulkAttendanceRequest $request, AttendanceSession $attendanceSession, SaveBulkAttendance $save): RedirectResponse
    {
        $save->execute($attendanceSession, $request->validated('records'), $request->user());

        return back()->with('success', 'Presensi seluruh siswa berhasil disimpan.');
    }

    public function enableCheckIn(AttendanceSession $attendanceSession, EnableAttendanceCheckIn $enable): RedirectResponse
    {
        $this->authorize('update', $attendanceSession);
        $validated = request()->validate([
            'minutes' => ['nullable', 'integer', 'min:5', 'max:180'],
        ]);

        $enable->execute($attendanceSession, request()->user(), (int) ($validated['minutes'] ?? EnableAttendanceCheckIn::DEFAULT_MINUTES));

        return back()->with('success', 'QR/link presensi aktif. Siswa dapat scan dan check-in selama batas waktu yang dipilih.');
    }

    public function disableCheckIn(AttendanceSession $attendanceSession): RedirectResponse
    {
        $this->authorize('update', $attendanceSession);
        $attendanceSession->update(['check_in_enabled' => false]);

        return back()->with('success', 'QR/link presensi dinonaktifkan. Guru/coach masih dapat mengisi presensi manual.');
    }

    public function close(AttendanceSession $attendanceSession, CloseAttendanceSession $close): RedirectResponse
    {
        $this->authorize('close', $attendanceSession);
        $close->execute($attendanceSession, request()->user());

        return back()->with('success', 'Sesi presensi ditutup. Perubahan berikutnya wajib melalui koreksi ber-audit.');
    }

    public function amend(
        AmendAttendanceRecordRequest $request,
        AttendanceRecord $attendanceRecord,
        AmendClosedAttendanceRecord $amend,
    ): RedirectResponse {
        $amend->execute($attendanceRecord, $request->validated(), $request->user());

        return back()->with('success', 'Koreksi presensi disimpan beserta alasan dan riwayat perubahan.');
    }

    private function loadAttendanceSession(AttendanceSession $attendanceSession): void
    {
        $attendanceSession->load([
            'learningSession:id,session_number,title',
            'academicYear:id,name',
            'schoolClass:id,name',
            'records' => fn ($query) => $query->with(['student:id,name,email', 'logs.actor:id,name'])
                ->join('users', 'attendance_records.user_id', '=', 'users.id')
                ->orderBy('users.name')
                ->select('attendance_records.*'),
            'opener:id,name',
            'closer:id,name',
        ]);
    }
}
