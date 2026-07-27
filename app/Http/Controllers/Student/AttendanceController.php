<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\AttendanceSummaryService;
use App\Services\ProgramContextService;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(AttendanceSummaryService $summaries): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);
        $student = request()->user();
        $activeMembership = app(ProgramContextService::class)->studentActiveMembership($student);
        $academicYearId = $activeMembership?->academic_year_id ?? $student->studentProfile?->schoolClass?->academic_year_id;
        $programBatchId = $activeMembership?->program_batch_id;
        $records = AttendanceRecord::query()
            ->where('user_id', $student->id)
            ->whereHas('attendanceSession', fn ($query) => $query
                ->where('status', AttendanceSessionStatus::Closed->value)
                ->when($academicYearId, fn ($query, int $year) => $query->where('academic_year_id', $year))
                ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))
            ->with(['attendanceSession.learningSession:id,session_number,title', 'attendanceSession.schoolClass:id,name'])
            ->latest('recorded_at')
            ->paginate(12);

        return view('student.attendance.index', [
            'records' => $records,
            'summary' => $summaries->forStudent($student, $academicYearId, $programBatchId),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }
}
