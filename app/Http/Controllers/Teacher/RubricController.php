<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\RubricRequest;
use App\Models\AcademicYear;
use App\Models\Rubric;
use App\Services\ProgramContextService;
use App\Services\RubricService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RubricController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Rubric::class);
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
        $rubrics = Rubric::withCount(['criteria', 'assignments'])
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->latest()
            ->paginate(12);

        return view('teacher.rubrics.index', compact('rubrics'));
    }

    public function create(): View
    {
        $this->authorize('create', Rubric::class);

        return view('teacher.rubrics.form', ['rubric' => new Rubric, 'academicYears' => AcademicYear::latest('starts_on')->get()]);
    }

    public function store(RubricRequest $request, RubricService $service): RedirectResponse
    {
        $r = $service->save(null, $request->validated(), $request->user());

        return redirect()->route('teacher.rubrics.show', $r)->with('success', 'Rubrik berhasil dibuat.');
    }

    public function show(Rubric $rubric): View
    {
        $this->authorize('view', $rubric);
        $rubric->load('criteria.levels');

        return view('teacher.rubrics.show', compact('rubric'));
    }

    public function edit(Rubric $rubric): View
    {
        $this->authorize('update', $rubric);
        $rubric->load('criteria.levels');

        return view('teacher.rubrics.form', ['rubric' => $rubric, 'academicYears' => AcademicYear::latest('starts_on')->get()]);
    }

    public function update(RubricRequest $request, Rubric $rubric, RubricService $service): RedirectResponse
    {
        $service->save($rubric, $request->validated(), $request->user());

        return redirect()->route('teacher.rubrics.show', $rubric)->with('success', 'Rubrik berhasil diperbarui.');
    }

    public function destroy(Rubric $rubric): RedirectResponse
    {
        $this->authorize('delete', $rubric);
        $rubric->delete();

        return redirect()->route('teacher.rubrics.index')->with('success', 'Rubrik dihapus.');
    }
}
