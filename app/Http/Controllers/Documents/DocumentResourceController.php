<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\DocumentIndexRequest;
use App\Http\Requests\Documents\DocumentResourceRequest;
use App\Models\DocumentResource;
use App\Services\DocumentAccessService;
use App\Services\DocumentResourceService;
use App\Services\ProgramContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocumentResourceController extends Controller
{
    public function index(DocumentIndexRequest $request, DocumentAccessService $access): View
    {
        $filters = $request->validated();
        $resources = $this->filteredQuery($access->queryFor($request->user()), $filters)
            ->with(['academicYear:id,name', 'creator:id,name', 'programBatch.program:id,name', 'programBatch.institution:id,name'])
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('documents.index', [
            'resources' => $resources,
            'academicYears' => app(ProgramContextService::class)->academicYears($request->user(), ['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function show(DocumentResource $documentResource): View
    {
        $this->authorize('view', $documentResource);
        $documentResource->load(['academicYear:id,name', 'creator:id,name', 'updater:id,name', 'logs.user:id,name', 'programBatch.program:id,name', 'programBatch.institution:id,name']);

        return view('documents.show', compact('documentResource'));
    }

    public function create(): View
    {
        $this->authorize('create', DocumentResource::class);

        return view('documents.create', $this->formData());
    }

    public function store(DocumentResourceRequest $request, DocumentResourceService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->parsedDriveUrl(), $request->user());

        return redirect()->route('documents.index')->with('success', 'Dokumen berhasil ditambahkan secara manual.');
    }

    public function edit(DocumentResource $documentResource): View
    {
        $this->authorize('update', $documentResource);

        return view('documents.edit', ['documentResource' => $documentResource, ...$this->formData()]);
    }

    public function update(DocumentResourceRequest $request, DocumentResource $documentResource, DocumentResourceService $service): RedirectResponse
    {
        $service->update($documentResource, $request->validated(), $request->parsedDriveUrl(), $request->user());

        return redirect()->route('documents.index')->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function publish(DocumentResource $documentResource, DocumentResourceService $service): RedirectResponse
    {
        $this->authorize('publish', $documentResource);
        $service->publish($documentResource, request()->user());

        return back()->with('success', 'Dokumen berhasil dipublikasikan.');
    }

    public function archive(DocumentResource $documentResource, DocumentResourceService $service): RedirectResponse
    {
        $this->authorize('update', $documentResource);
        $service->archive($documentResource, request()->user());

        return back()->with('success', 'Dokumen berhasil diarsipkan dan tidak lagi terlihat oleh audience.');
    }

    public function pin(DocumentResource $documentResource, DocumentResourceService $service): RedirectResponse
    {
        $this->authorize('update', $documentResource);
        $service->togglePin($documentResource, request()->user());

        return back()->with('success', 'Status pin dokumen berhasil diperbarui.');
    }

    public function destroy(DocumentResource $documentResource, DocumentResourceService $service): RedirectResponse
    {
        $this->authorize('delete', $documentResource);
        $service->delete($documentResource, request()->user());

        return redirect()->route('documents.index')->with('success', 'Dokumen berhasil dihapus dari Document Center.');
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(Builder $query, array $filters): Builder
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId(request()->user());

        return $query
            ->when($activeBatchId, fn (Builder $query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['audience'] ?? null, fn (Builder $query, string $audience) => $query->where('audience', $audience))
            ->when($filters['academic_year_id'] ?? null, fn (Builder $query, int|string $year) => $query->where('academic_year_id', $year))
            ->when($filters['semester'] ?? null, fn (Builder $query, int|string $semester) => $query->where('semester', $semester))
            ->when(($filters['status'] ?? null) === 'draft', fn (Builder $query) => $query->whereNull('published_at'))
            ->when(($filters['status'] ?? null) === 'published', fn (Builder $query) => $query->published())
            ->when(($filters['status'] ?? null) === 'archived', fn (Builder $query) => $query->where('is_active', false)->whereNotNull('published_at'))
            ->when(array_key_exists('pinned', $filters), fn (Builder $query) => $query->where('is_pinned', $filters['pinned']));
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return ['academicYears' => app(ProgramContextService::class)->academicYears(request()->user(), ['id', 'name'])];
    }
}
