<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ReviewPortfolioRequest;
use App\Models\PortfolioItem;
use App\Services\PortfolioService;
use App\Services\ProgramContextService;
use App\Services\PortfolioWorkTypeOptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PortfolioItem::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'work_type' => ['nullable', 'string'], 'visibility' => ['nullable', 'string'], 'approval_status' => ['nullable', 'string']]);
        $programContext = app(ProgramContextService::class);
        $activeBatch = $programContext->activeBatch($request->user());
        $activeBatchId = $activeBatch?->id;
        $items = PortfolioItem::with(['owner:id,name', 'schoolClass:id,name'])
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('title', 'like', "%$v%")->orWhereHas('owner', fn ($u) => $u->where('name', 'like', "%$v%"))))
            ->when($filters['work_type'] ?? null, fn ($q, $v) => $q->where('work_type', $v))
            ->when($filters['visibility'] ?? null, fn ($q, $v) => $q->where('visibility', $v))
            ->when($filters['approval_status'] ?? null, fn ($q, $v) => $q->where('approval_status', $v))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('teacher.portfolio.index', [
            'items' => $items,
            'filters' => $filters,
            'workTypes' => app(PortfolioWorkTypeOptionService::class)->activeFor($activeBatch?->program),
            'visibilities' => PortfolioVisibility::cases(),
            'approvalStatuses' => PortfolioApprovalStatus::cases(),
        ]);
    }

    public function show(PortfolioItem $portfolioItem): View
    {
        $this->authorize('view', $portfolioItem);
        $portfolioItem->load(['owner:id,name', 'schoolClass:id,name', 'submission.grade', 'initialVersion.files', 'finalVersion.files', 'audits.actor:id,name']);

        return view('teacher.portfolio.show', ['item' => $portfolioItem]);
    }

    public function review(ReviewPortfolioRequest $request, PortfolioItem $portfolioItem, PortfolioService $service): RedirectResponse
    {
        $service->review($portfolioItem, $request->string('decision')->toString(), $request->input('approval_note'), $request->user());

        return back()->with('success', 'Keputusan approval berhasil disimpan.');
    }

    public function feature(PortfolioItem $portfolioItem, PortfolioService $service): RedirectResponse
    {
        $this->authorize('feature', $portfolioItem);
        $service->toggleFeatured($portfolioItem, request()->user());

        return back()->with('success', 'Status featured berhasil diperbarui.');
    }
}
