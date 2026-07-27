<?php

namespace App\Services;

use App\Enums\ImportantNoteStatus;
use App\Enums\LearningSessionStatus;
use App\Enums\TeacherActivityStatus;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\DocumentResource;
use App\Models\Grade;
use App\Models\ImportantNote;
use App\Models\LearningSession;
use App\Models\TeacherActivityLog;

class PrincipalDashboardService
{
    public function build(?int $yearId = null): array
    {
        $years = AcademicYear::orderByDesc('starts_on')->get();
        $year = $years->firstWhere('id', $yearId) ?? $years->firstWhere('is_active', true) ?? $years->first();
        $activeBatchId = request()->user() ? app(ProgramContextService::class)->activeBatchId(request()->user()) : null;
        if (! $year) {
            return ['years' => $years, 'year' => null, 'summary' => array_fill_keys(['sessions', 'total_sessions', 'attendance', 'average_grade', 'teacher_logs', 'open_notes', 'documents'], 0), 'attendance' => [], 'grades' => [], 'teacherLogs' => [], 'notes' => collect(), 'documents' => collect()];
        }
        $attendanceCounts = AttendanceRecord::query()->selectRaw('status, COUNT(*) total')->whereHas('attendanceSession', fn ($q) => $q->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', 'closed'))->groupBy('status')->pluck('total', 'status');
        $attendanceTotal = $attendanceCounts->sum();
        $attended = (int) ($attendanceCounts['present'] ?? 0) + (int) ($attendanceCounts['late'] ?? 0);
        $grades = Grade::query()->where('is_published', true)->whereHas('submission.assignment', fn ($q) => $q->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)));
        $scores = (clone $grades)->pluck('total_score');
        $logCounts = TeacherActivityLog::query()->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        $completedSessions = LearningSession::where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', LearningSessionStatus::Completed)->count();
        $totalSessions = LearningSession::where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->count();

        return ['years' => $years, 'year' => $year,
            'summary' => ['sessions' => $completedSessions, 'total_sessions' => $totalSessions, 'attendance' => $attendanceTotal ? (int) round($attended / $attendanceTotal * 100) : 0, 'average_grade' => $scores->isNotEmpty() ? (int) round($scores->avg()) : 0, 'teacher_logs' => (int) ($logCounts[TeacherActivityStatus::Verified->value] ?? 0), 'open_notes' => ImportantNote::where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->whereIn('status', [ImportantNoteStatus::Open, ImportantNoteStatus::InProgress])->count(), 'documents' => DocumentResource::published()->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->count()],
            'attendance' => $attendanceCounts->map(fn ($v) => (int) $v)->all(),
            'grades' => ['90-100' => $scores->filter(fn ($v) => $v >= 90)->count(), '80-89' => $scores->filter(fn ($v) => $v >= 80 && $v < 90)->count(), '70-79' => $scores->filter(fn ($v) => $v >= 70 && $v < 80)->count(), '<70' => $scores->filter(fn ($v) => $v < 70)->count()],
            'teacherLogs' => collect(TeacherActivityStatus::cases())->mapWithKeys(fn ($status) => [$status->label() => (int) ($logCounts[$status->value] ?? 0)])->all(),
            'notes' => ImportantNote::with('creator:id,name')->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->latest('note_date')->limit(6)->get(),
            'documents' => DocumentResource::published()->with('academicYear:id,name')->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderByDesc('is_pinned')->latest('published_at')->limit(6)->get(),
        ];
    }
}
