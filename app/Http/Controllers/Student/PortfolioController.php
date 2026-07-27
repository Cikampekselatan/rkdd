<?php

namespace App\Http\Controllers\Student;

use App\Enums\PortfolioVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\PortfolioItemRequest;
use App\Models\Grade;
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
        $programBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
        $items = PortfolioItem::where('user_id', $request->user()->id)
            ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->latest()
            ->paginate(12);

        return view('student.portfolio.index', compact('items'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PortfolioItem::class);

        return view('student.portfolio.form', ['item' => new PortfolioItem, ...$this->formData($request)]);
    }

    public function store(PortfolioItemRequest $request, PortfolioService $service): RedirectResponse
    {
        $item = $service->save(null, $request->validated(), $request->user(), $request->allFiles());

        return redirect()->route('student.portfolio.show', $item)->with('success', 'Karya berhasil ditambahkan ke portofolio.');
    }

    public function show(PortfolioItem $portfolioItem): View
    {
        $this->authorize('view', $portfolioItem);
        $portfolioItem->load(['owner:id,name', 'submission.grade', 'initialVersion.files', 'finalVersion.files', 'schoolClass', 'audits.actor:id,name']);

        return view('student.portfolio.show', ['item' => $portfolioItem]);
    }

    public function edit(Request $request, PortfolioItem $portfolioItem): View
    {
        $this->authorize('update', $portfolioItem);

        return view('student.portfolio.form', ['item' => $portfolioItem, ...$this->formData($request, $portfolioItem)]);
    }

    public function update(PortfolioItemRequest $request, PortfolioItem $portfolioItem, PortfolioService $service): RedirectResponse
    {
        $service->save($portfolioItem, $request->validated(), $request->user(), $request->allFiles());

        return redirect()->route('student.portfolio.show', $portfolioItem)->with('success', 'Portofolio berhasil diperbarui dan approval ditinjau ulang.');
    }

    public function destroy(PortfolioItem $portfolioItem, PortfolioService $service): RedirectResponse
    {
        $this->authorize('delete', $portfolioItem);
        $service->delete($portfolioItem, request()->user());

        return redirect()->route('student.portfolio.index')->with('success', 'Karya dihapus dari portofolio.');
    }

    public function print(PortfolioItem $portfolioItem): View
    {
        $this->authorize('view', $portfolioItem);
        $portfolioItem->load(['owner:id,name', 'schoolClass:id,name', 'submission.grade']);

        return view('student.portfolio.print', ['item' => $portfolioItem]);
    }

    private function formData(Request $request, ?PortfolioItem $item = null): array
    {
        $eligibleGrades = Grade::query()
            ->where('is_published', true)
            ->whereHas('submission', function ($query) use ($request, $item): void {
                $programBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
                $query->where('user_id', $request->user()->id)
                    ->whereHas('assignment', fn ($assignment) => $assignment->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))
                    ->where(function ($available) use ($item): void {
                        $available->whereDoesntHave('portfolioItem');
                        if ($item?->submission_id) {
                            $available->orWhereKey($item->submission_id);
                        }
                    });
            })
            ->with('submission.assignment')
            ->get();

        $activeBatch = app(ProgramContextService::class)->activeBatch($request->user());

        return [
            'workTypes' => app(PortfolioWorkTypeOptionService::class)->activeFor($activeBatch?->program),
            'visibilities' => PortfolioVisibility::cases(),
            'eligibleGrades' => $eligibleGrades,
        ];
    }
}
