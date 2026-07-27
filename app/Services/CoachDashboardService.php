<?php

namespace App\Services;

use App\Enums\ImportantNoteStatus;
use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Models\ImportantNote;
use App\Models\PortfolioItem;

class CoachDashboardService
{
    public function __construct(private readonly TeacherDashboardService $teacherDashboard) {}

    public function build(?int $yearId = null): array
    {
        $data = $this->teacherDashboard->build($yearId);
        $year = $data['year'];

        if ($year === null) {
            return [
                ...$data,
                'coachSummary' => ['projects' => 0, 'notes_waiting' => 0],
                'notesWaiting' => collect(),
                'recentProjects' => collect(),
            ];
        }

        $notesWaiting = ImportantNote::query()
            ->with('creator:id,name')
            ->where('academic_year_id', $year->id)
            ->whereNull('coach_initialed_at')
            ->where('status', '!=', ImportantNoteStatus::Verified)
            ->latest('note_date')
            ->limit(6)
            ->get();

        $visibleProjects = PortfolioItem::query()
            ->where('academic_year_id', $year->id)
            ->where('approval_status', PortfolioApprovalStatus::Approved)
            ->whereIn('visibility', [PortfolioVisibility::ClassRoom, PortfolioVisibility::School, PortfolioVisibility::PublicApproved]);

        return [
            ...$data,
            'coachSummary' => [
                'projects' => (clone $visibleProjects)->count(),
                'notes_waiting' => $notesWaiting->count(),
            ],
            'notesWaiting' => $notesWaiting,
            'recentProjects' => $visibleProjects
                ->with(['owner:id,name', 'schoolClass:id,name'])
                ->latest('updated_at')
                ->limit(6)
                ->get(),
        ];
    }
}
