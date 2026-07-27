<?php

namespace App\Http\Controllers;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Models\PortfolioItem;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['program' => ['nullable', 'string', 'max:120']]);
        $programs = Program::query()
            ->where('is_active', true)
            ->whereHas('batches', fn ($query) => $query->whereHas('portfolioItems', fn ($items) => $items->where('visibility', PortfolioVisibility::PublicApproved)->where('approval_status', PortfolioApprovalStatus::Approved)))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $selectedProgram = isset($filters['program']) ? $programs->firstWhere('slug', $filters['program']) : null;
        $items = PortfolioItem::where('visibility', PortfolioVisibility::PublicApproved)
            ->where('approval_status', PortfolioApprovalStatus::Approved)
            ->when($selectedProgram, fn ($query) => $query->whereHas('programBatch', fn ($batch) => $batch->where('program_id', $selectedProgram->id)))
            ->with(['owner:id,name', 'programBatch.program:id,name,slug'])
            ->orderByDesc('is_featured')
            ->latest('approved_at')
            ->paginate(12)
            ->withQueryString();

        return view('portfolio.public-index', compact('items', 'programs', 'selectedProgram'));
    }

    public function show(PortfolioItem $portfolioItem): View
    {
        abort_unless($portfolioItem->visibility === PortfolioVisibility::PublicApproved && $portfolioItem->approval_status === PortfolioApprovalStatus::Approved, 404);
        $portfolioItem->load(['owner:id,name', 'schoolClass:id,name', 'submission.grade', 'initialVersion.files', 'finalVersion.files']);

        return view('portfolio.public-show', ['item' => $portfolioItem]);
    }
}
