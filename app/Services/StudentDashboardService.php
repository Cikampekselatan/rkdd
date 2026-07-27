<?php

namespace App\Services;

use App\Enums\AnnouncementAudience;
use App\Enums\LearningSessionStatus;
use App\Enums\SubmissionStatus;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\Grade;
use App\Models\GroupProjectAssessment;
use App\Models\LearningSession;
use App\Models\MonthlyStudentAssessment;
use App\Models\PortfolioItem;
use App\Models\StudentLearningProgress;
use App\Models\Submission;
use App\Models\User;

class StudentDashboardService
{
    public function __construct(private readonly AttendanceSummaryService $attendanceSummaries) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $student): array
    {
        $student->loadMissing([
            'studentProfile.schoolClass.academicYear',
            'onboardingResponse',
        ]);

        $activeMembership = app(ProgramContextService::class)->studentActiveMembership($student);
        $academicYearId = $activeMembership?->academic_year_id ?? $student->studentProfile?->schoolClass?->academic_year_id;
        $programBatchId = $activeMembership?->program_batch_id
            ?? $student->studentProfile?->program_batch_id
            ?? $student->classMemberships()->where('status', 'active')->value('program_batch_id');
        $totalSessions = $academicYearId
            ? LearningSession::query()->where('academic_year_id', $academicYearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->count()
            : 0;
        $completedSessions = $academicYearId
            ? StudentLearningProgress::query()
                ->where('user_id', $student->id)
                ->whereNotNull('completed_at')
                ->whereHas('learningSession', fn ($query) => $query
                    ->where('academic_year_id', $academicYearId)
                    ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                    ->whereIn('status', LearningSessionStatus::studentVisibleValues()))
                ->count()
            : 0;
        $continueLearning = $academicYearId
            ? LearningSession::query()
                ->with(['module', 'materials', 'progressRecords' => fn ($query) => $query->where('user_id', $student->id)])
                ->where('academic_year_id', $academicYearId)
                ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->whereIn('status', LearningSessionStatus::studentVisibleValues())
                ->whereDoesntHave('progressRecords', fn ($query) => $query
                    ->where('user_id', $student->id)
                    ->whereNotNull('completed_at'))
                ->orderBy('session_number')
                ->first()
            : null;
        $attendanceSummary = $this->attendanceSummaries->forStudent($student, $academicYearId, $programBatchId);
        $attendanceHistory = $academicYearId
            ? AttendanceRecord::query()
                ->where('user_id', $student->id)
                ->whereHas('attendanceSession', fn ($query) => $query
                    ->where('academic_year_id', $academicYearId)
                    ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                    ->where('status', 'closed'))
                ->with('attendanceSession.learningSession:id,session_number,title')
                ->latest('recorded_at')
                ->limit(5)
                ->get()
            : collect();
        $classId = $activeMembership?->class_id ?? $student->studentProfile?->class_id;
        $upcomingAssignments = $classId ? Assignment::query()->where('class_id', $classId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('is_published', true)->where('due_at', '>=', now())->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $student->id)->where('status', '!=', SubmissionStatus::Draft->value))->orderBy('due_at')->limit(5)->get() : collect();
        $revisionAssignments = Submission::query()->where('user_id', $student->id)->where('status', SubmissionStatus::RevisionRequested->value)->whereHas('assignment', fn ($query) => $query->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->with('assignment')->latest('revision_requested_at')->limit(5)->get();
        $latestGrades = Grade::query()->where('is_published', true)->whereHas('submission', fn ($query) => $query->where('user_id', $student->id)->whereHas('assignment', fn ($assignment) => $assignment->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))))->with('submission.assignment')->latest('published_at')->limit(5)->get();
        $latestMonthlyAssessment = MonthlyStudentAssessment::query()
            ->where('user_id', $student->id)
            ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('is_published', true)
            ->latest('published_at')
            ->latest('assessed_at')
            ->first();
        $latestGroupAssessment = GroupProjectAssessment::query()
            ->where('is_published', true)
            ->whereHas('groupProject.projectGroup', fn ($query) => $query->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->whereHas('activeMembers', fn ($member) => $member->where('user_id', $student->id)))
            ->with('groupProject.projectGroup')
            ->latest('published_at')
            ->first();
        $averageGrade = $latestGrades->isNotEmpty() ? (int) round(Grade::query()->where('is_published', true)->whereHas('submission', fn ($query) => $query->where('user_id', $student->id)->whereHas('assignment', fn ($assignment) => $assignment->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))))->avg('total_score')) : 0;
        $portfolioCount = PortfolioItem::query()->where('user_id', $student->id)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->count();
        $featuredPortfolio = PortfolioItem::query()
            ->where('user_id', $student->id)
            ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('is_featured', true)
            ->latest('approved_at')
            ->limit(3)
            ->get();
        $announcements = Announcement::query()->visible()->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where(function ($query) use ($classId, $academicYearId, $programBatchId): void {
            $query->whereIn('audience', [AnnouncementAudience::All->value, AnnouncementAudience::Students->value])
                ->orWhere(fn ($class) => $class->where('audience', AnnouncementAudience::ClassRoom->value)->where('class_id', $classId))
                ->orWhere(fn ($session) => $session->where('audience', AnnouncementAudience::Session->value)->whereHas('learningSession', fn ($item) => $item->where('academic_year_id', $academicYearId)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))));
        })->orderByDesc('is_pinned')->latest('published_at')->limit(3)->get();

        return [
            'student' => $student,
            'profile' => $student->studentProfile,
            'schoolClass' => $activeMembership?->schoolClass ?? $student->studentProfile?->schoolClass,
            'activeMembership' => $activeMembership,
            'activeProgramBatch' => $activeMembership?->programBatch,
            'interests' => $student->onboardingResponse?->interests ?? [],
            'summary' => [
                'completed_sessions' => $completedSessions,
                'total_sessions' => $totalSessions,
                'active_assignments' => $upcomingAssignments->count(),
                'revisions' => $revisionAssignments->count(),
                'average_grade' => $averageGrade,
                'attendance_rate' => $attendanceSummary['percentage'],
                'portfolio_count' => $portfolioCount,
            ],
            'continueLearning' => $continueLearning,
            'upcomingAssignments' => $upcomingAssignments,
            'revisionAssignments' => $revisionAssignments,
            'latestGrades' => $latestGrades,
            'latestMonthlyAssessment' => $latestMonthlyAssessment,
            'latestGroupAssessment' => $latestGroupAssessment,
            'attendanceHistory' => $attendanceHistory,
            'featuredPortfolio' => $featuredPortfolio,
            'announcements' => $announcements,
        ];
    }
}
