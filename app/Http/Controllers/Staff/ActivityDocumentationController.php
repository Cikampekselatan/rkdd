<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ActivityDocumentationRequest;
use App\Models\AcademicYear;
use App\Models\ActivityDocumentation;
use App\Services\ActivityDocumentationService;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityDocumentationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityDocumentation::class);
        $filters = $request->validate(['academic_year_id' => ['nullable', 'exists:academic_years,id'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'q' => ['nullable', 'string', 'max:100']]);
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
        $docs = ActivityDocumentation::query()
            ->with(['creator.roles', 'academicYear:id,name', 'programBatch.program:id,name', 'programBatch.institution:id,name'])
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->latest('activity_date')
            ->when($filters['academic_year_id'] ?? null, fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('activity_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('activity_date', '<=', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($scope) => $scope->where('title', 'like', '%'.$v.'%')->orWhere('description', 'like', '%'.$v.'%')))
            ->paginate(12)
            ->withQueryString();

        return view('staff.activity-documentations.index', ['docs' => $docs, 'filters' => $filters, 'academicYears' => AcademicYear::query()->latest('starts_on')->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', ActivityDocumentation::class);

        return view('staff.activity-documentations.form', ['documentation' => new ActivityDocumentation, 'academicYears' => AcademicYear::query()->latest('starts_on')->get()]);
    }

    public function store(ActivityDocumentationRequest $request, ActivityDocumentationService $service): RedirectResponse
    {
        $documentation = $service->save(null, $request->validated(), $request->file('photo'), $request->user());

        return redirect()->route('activity-documentations.show', $documentation)->with('success', 'Dokumentasi kegiatan berhasil disimpan.');
    }

    public function show(ActivityDocumentation $activityDocumentation): View
    {
        $this->authorize('view', $activityDocumentation);
        $activityDocumentation->load(['creator.roles', 'academicYear:id,name', 'programBatch.program:id,name', 'programBatch.institution:id,name']);

        return view('staff.activity-documentations.show', ['documentation' => $activityDocumentation]);
    }

    public function edit(ActivityDocumentation $activityDocumentation): View
    {
        $this->authorize('update', $activityDocumentation);

        return view('staff.activity-documentations.form', ['documentation' => $activityDocumentation, 'academicYears' => AcademicYear::query()->latest('starts_on')->get()]);
    }

    public function update(ActivityDocumentationRequest $request, ActivityDocumentation $activityDocumentation, ActivityDocumentationService $service): RedirectResponse
    {
        $service->save($activityDocumentation, $request->validated(), $request->file('photo'), $request->user());

        return redirect()->route('activity-documentations.show', $activityDocumentation)->with('success', 'Dokumentasi kegiatan berhasil diperbarui.');
    }

    public function destroy(ActivityDocumentation $activityDocumentation, ActivityDocumentationService $service): RedirectResponse
    {
        $this->authorize('delete', $activityDocumentation);
        $service->delete($activityDocumentation);

        return redirect()->route('activity-documentations.index')->with('success', 'Dokumentasi kegiatan berhasil dihapus.');
    }
}
