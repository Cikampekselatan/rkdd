<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ImportantNoteStatus;
use App\Enums\LearningSessionStatus;
use App\Enums\StudentMembershipStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TeacherActivityStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\ClassStudent;
use App\Models\Grade;
use App\Models\ImportantNote;
use App\Models\LearningSession;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\TeacherActivityLog;
use App\Models\User;

class TeacherDashboardService
{
    public function build(?int $yearId = null): array
    {
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();
        $year = $years->firstWhere('id', $yearId) ?? $years->firstWhere('is_active', true) ?? $years->first();
        $activeBatchId = request()->user() ? app(ProgramContextService::class)->activeBatchId(request()->user()) : null;
        if (! $year) {
            return $this->empty($years);
        }

        $activeStudents = User::query()->where('status', UserStatus::Active)->whereHas('classMemberships', fn ($q) => $q->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', 'active'))->count();
        $newRegistrations = ClassStudent::query()->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', 'active')->where('joined_at', '>=', now()->startOfMonth())->count();
        $onboarding = StudentProfile::query()->where('membership_status', StudentMembershipStatus::Onboarding)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->whereHas('schoolClass', fn ($q) => $q->where('academic_year_id', $year->id))->count();
        $sessionsCompleted = LearningSession::query()->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', LearningSessionStatus::Completed)->count();
        $totalSessions = LearningSession::query()->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->count();
        $visibleSessions = LearningSession::where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->whereIn('status', LearningSessionStatus::studentVisibleValues())->count();
        $ungraded = Submission::query()->whereIn('status', [SubmissionStatus::Submitted, SubmissionStatus::Late, SubmissionStatus::UnderReview, SubmissionStatus::Resubmitted])->whereHas('assignment', fn ($q) => $q->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->whereDoesntHave('grade', fn ($q) => $q->where('is_published', true))->count();
        $revisions = Submission::query()->where('status', SubmissionStatus::RevisionRequested)->whereHas('assignment', fn ($q) => $q->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->count();
        $attendance = $this->attendance($year->id, $activeBatchId);

        return [
            'years' => $years, 'year' => $year,
            'kpis' => ['active_students' => $activeStudents, 'new_registrations' => $newRegistrations, 'onboarding' => $onboarding, 'sessions_completed' => $sessionsCompleted, 'ungraded' => $ungraded, 'revisions' => $revisions, 'attendance_rate' => $attendance['rate'], 'open_notes' => ImportantNote::where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->whereIn('status', [ImportantNoteStatus::Open, ImportantNoteStatus::InProgress])->count(), 'pending_teacher_logs' => TeacherActivityLog::where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', TeacherActivityStatus::Submitted)->count()],
            'attentionStudents' => $this->attentionStudents($year->id, $activeBatchId),
            'charts' => ['attendance' => $attendance['statuses'], 'grades' => $this->gradeDistribution($year->id, $activeBatchId), 'competencies' => $this->competencyDistribution($year->id, $activeBatchId), 'progress' => ['completed' => $sessionsCompleted, 'visible' => $visibleSessions, 'total' => $totalSessions]],
            'recentNotes' => ImportantNote::with('creator:id,name')->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->whereIn('status', [ImportantNoteStatus::Open, ImportantNoteStatus::InProgress])->latest('note_date')->limit(5)->get(),
            'pendingLogs' => TeacherActivityLog::with('teacher:id,name')->where('academic_year_id', $year->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', TeacherActivityStatus::Submitted)->oldest('submitted_at')->limit(5)->get(),
        ];
    }

    private function attendance(int $yearId, ?int $programBatchId = null): array
    {
        $counts = AttendanceRecord::query()->selectRaw('status, COUNT(*) as total')->whereHas('attendanceSession', fn ($q) => $q->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', 'closed'))->groupBy('status')->pluck('total', 'status');
        $statuses = collect(AttendanceStatus::cases())->mapWithKeys(fn ($status) => [$status->value => (int) ($counts[$status->value] ?? 0)])->all();
        $total = array_sum($statuses);
        $attended = $statuses['present'] + $statuses['late'];

        return ['statuses' => $statuses, 'rate' => $total ? (int) round($attended / $total * 100) : 0];
    }

    private function attentionStudents(int $yearId, ?int $programBatchId = null)
    {
        $students = User::query()->select('id', 'name')->where('status', UserStatus::Active)->whereHas('classMemberships', fn ($q) => $q->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', 'active'))->with(['studentProfile.schoolClass:id,name'])->get();
        $attendance = AttendanceRecord::query()->selectRaw("user_id, COUNT(*) total, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) attended")->whereHas('attendanceSession', fn ($q) => $q->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', 'closed'))->groupBy('user_id')->get()->keyBy('user_id');
        $submissionCounts = Submission::query()->selectRaw("user_id, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) late_count, SUM(CASE WHEN status = 'revision_requested' THEN 1 ELSE 0 END) revision_count")->whereHas('assignment', fn ($q) => $q->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->groupBy('user_id')->get()->keyBy('user_id');
        $dueByClass = Assignment::query()->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('is_published', true)->where('due_at', '<', now())->selectRaw('class_id, COUNT(*) total')->groupBy('class_id')->pluck('total', 'class_id');
        $submitted = Submission::query()->selectRaw('user_id, COUNT(*) total')->where('status', '!=', SubmissionStatus::Draft)->whereHas('assignment', fn ($q) => $q->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('is_published', true)->where('due_at', '<', now()))->groupBy('user_id')->pluck('total', 'user_id');
        $grades = Grade::query()->where('is_published', true)->whereHas('submission.assignment', fn ($q) => $q->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->with('submission:id,user_id')->orderByDesc('published_at')->get()->groupBy(fn ($grade) => $grade->submission->user_id);

        return $students->map(function ($student) use ($attendance, $submissionCounts, $dueByClass, $submitted, $grades): array {
            $reasons = [];
            $record = $attendance->get($student->id);
            $rate = $record?->total ? (int) round($record->attended / $record->total * 100) : 100;
            if ($record && $rate < 75) {
                $reasons[] = 'Kehadiran '.$rate.'%';
            }
            $counts = $submissionCounts->get($student->id);
            if (($counts?->late_count ?? 0) >= 2) {
                $reasons[] = $counts->late_count.' tugas terlambat';
            } if (($counts?->revision_count ?? 0) > 0) {
                $reasons[] = $counts->revision_count.' revisi belum selesai';
            }
            $classId = $student->studentProfile?->class_id;
            $missing = max(0, (int) ($dueByClass[$classId] ?? 0) - (int) ($submitted[$student->id] ?? 0));
            if ($missing > 0) {
                $reasons[] = $missing.' tugas belum dikumpulkan';
            }
            $studentGrades = $grades->get($student->id, collect())->values();
            $average = $studentGrades->isNotEmpty() ? (int) round($studentGrades->avg('total_score')) : null;
            if ($average !== null && $average < 70) {
                $reasons[] = 'Rata-rata nilai '.$average;
            } if ($studentGrades->count() >= 2 && (float) $studentGrades[0]->total_score + 5 <= (float) $studentGrades[1]->total_score) {
                $reasons[] = 'Nilai terbaru menurun';
            }

            return ['student' => $student, 'reasons' => $reasons, 'severity' => count($reasons), 'attendance_rate' => $rate, 'average_grade' => $average];
        })->filter(fn ($item) => $item['severity'] > 0)->sortByDesc('severity')->take(8)->values();
    }

    private function gradeDistribution(int $yearId, ?int $programBatchId = null): array
    {
        $scores = Grade::query()->where('is_published', true)->whereHas('submission.assignment', fn ($q) => $q->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->pluck('total_score');

        return ['90-100' => $scores->filter(fn ($v) => $v >= 90)->count(), '80-89' => $scores->filter(fn ($v) => $v >= 80 && $v < 90)->count(), '70-79' => $scores->filter(fn ($v) => $v >= 70 && $v < 80)->count(), '<70' => $scores->filter(fn ($v) => $v < 70)->count()];
    }

    private function competencyDistribution(int $yearId, ?int $programBatchId = null): array
    {
        $counts = Grade::query()->selectRaw('achievement_level, COUNT(*) total')->where('is_published', true)->whereHas('submission.assignment', fn ($q) => $q->where('academic_year_id', $yearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->groupBy('achievement_level')->pluck('total', 'achievement_level');

        return collect([1 => 'Perlu Pendampingan', 2 => 'Berkembang', 3 => 'Terampil', 4 => 'Kreator Mandiri'])->mapWithKeys(fn ($label, $level) => [$label => (int) ($counts[$level] ?? 0)])->all();
    }

    private function empty($years): array
    {
        return ['years' => $years, 'year' => null, 'kpis' => array_fill_keys(['active_students', 'new_registrations', 'onboarding', 'sessions_completed', 'ungraded', 'revisions', 'attendance_rate', 'open_notes', 'pending_teacher_logs'], 0), 'attentionStudents' => collect(), 'charts' => ['attendance' => [], 'grades' => [], 'competencies' => [], 'progress' => ['completed' => 0, 'visible' => 0, 'total' => 0]], 'recentNotes' => collect(), 'pendingLogs' => collect()];
    }
}
