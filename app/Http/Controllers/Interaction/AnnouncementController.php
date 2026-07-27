<?php

namespace App\Http\Controllers\Interaction;

use App\Enums\AnnouncementAudience;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Announcement::class);
        $user = $request->user();
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);
        $items = Announcement::query()
            ->with(['author:id,name', 'schoolClass:id,name', 'learningSession:id,title'])
            ->withExists(['readers as is_read' => fn ($q) => $q->where('users.id', $user->id)])
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId));
        if ($user->hasRole(RoleSlug::Student)) {
            $classIds = $user->classMemberships()->where('status', 'active')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->pluck('class_id');
            $activeMembership = app(ProgramContextService::class)->studentActiveMembership($user);
            $yearId = $activeMembership?->academic_year_id ?? $user->studentProfile?->schoolClass?->academic_year_id;
            $items->visible()->where(function ($q) use ($activeBatchId, $classIds, $yearId): void {
                $q->whereIn('audience', [AnnouncementAudience::All->value, AnnouncementAudience::Students->value])
                    ->orWhere(fn ($x) => $x->where('audience', AnnouncementAudience::ClassRoom->value)->whereIn('class_id', $classIds))
                    ->orWhere(fn ($x) => $x->where('audience', AnnouncementAudience::Session->value)->whereHas('learningSession', fn ($s) => $s->where('academic_year_id', $yearId)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))));
            });
        }
        $items = $items->orderByDesc('is_pinned')->orderByDesc('published_at')->paginate(12);

        return view('interactions.announcements.index', compact('items'));
    }

    public function show(Announcement $announcement, AnnouncementService $service): View
    {
        $this->authorize('view', $announcement);
        $announcement->load(['author:id,name', 'schoolClass:id,name', 'learningSession:id,title']);
        if (request()->user()->hasRole(RoleSlug::Student)) {
            $service->markRead($announcement, request()->user());
        }

        return view('interactions.announcements.show', compact('announcement'));
    }

    public function read(Announcement $announcement, AnnouncementService $service): RedirectResponse
    {
        $this->authorize('view', $announcement);
        $service->markRead($announcement, request()->user());

        return back();
    }
}
