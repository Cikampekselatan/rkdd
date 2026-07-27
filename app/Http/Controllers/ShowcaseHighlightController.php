<?php

namespace App\Http\Controllers;

use App\Enums\ShowcaseHighlightPeriod;
use App\Enums\ShowcaseMediaType;
use App\Http\Requests\ShowcaseHighlightRequest;
use App\Models\ShowcaseHighlight;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowcaseHighlightController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ShowcaseHighlight::class);

        $filters = $request->validate([
            'period' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());

        $highlights = ShowcaseHighlight::query()
            ->with(['creator:id,name'])
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->when($filters['period'] ?? null, fn ($query, string $period) => $query->where('period', $period))
            ->when($filters['q'] ?? null, fn ($query, string $q) => $query->where(fn ($inner) => $inner
                ->where('title', 'like', "%{$q}%")
                ->orWhere('student_name', 'like', "%{$q}%")))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('showcase-highlights.index', [
            'highlights' => $highlights,
            'filters' => $filters,
            'periods' => ShowcaseHighlightPeriod::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ShowcaseHighlight::class);

        return view('showcase-highlights.form', $this->formData(new ShowcaseHighlight));
    }

    public function store(ShowcaseHighlightRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['program_batch_id'] = app(ProgramContextService::class)->activeBatchId($request->user());
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        ShowcaseHighlight::query()->create($data);

        return redirect()->route('showcase-highlights.index')->with('success', 'Hasil terbaik publik berhasil ditambahkan.');
    }

    public function edit(ShowcaseHighlight $showcaseHighlight): View
    {
        $this->authorize('update', $showcaseHighlight);

        return view('showcase-highlights.form', $this->formData($showcaseHighlight));
    }

    public function update(ShowcaseHighlightRequest $request, ShowcaseHighlight $showcaseHighlight): RedirectResponse
    {
        $data = $request->validated();
        $data['program_batch_id'] = $showcaseHighlight->program_batch_id ?? app(ProgramContextService::class)->activeBatchId($request->user());
        $data['updated_by'] = $request->user()->id;

        $showcaseHighlight->update($data);

        return redirect()->route('showcase-highlights.index')->with('success', 'Hasil terbaik publik berhasil diperbarui.');
    }

    public function destroy(ShowcaseHighlight $showcaseHighlight): RedirectResponse
    {
        $this->authorize('delete', $showcaseHighlight);

        $showcaseHighlight->delete();

        return redirect()->route('showcase-highlights.index')->with('success', 'Hasil terbaik publik berhasil diarsipkan.');
    }

    private function formData(ShowcaseHighlight $highlight): array
    {
        return [
            'highlight' => $highlight,
            'periods' => ShowcaseHighlightPeriod::cases(),
            'mediaTypes' => ShowcaseMediaType::cases(),
        ];
    }
}
