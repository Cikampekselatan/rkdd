<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolClassRequest;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', SchoolClass::class);
        $activeBatchId = app(ProgramContextService::class)->activeBatchId(request()->user());

        return view('admin.classes.index', [
            'classes' => SchoolClass::query()
                ->with(['academicYear:id,name,is_active', 'homeroomTeacher:id,name'])
                ->withCount('studentProfiles')
                ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SchoolClass::class);

        return view('admin.classes.create', $this->formData());
    }

    public function store(SchoolClassRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['program_batch_id'] = $data['program_batch_id']
            ?? app(ProgramContextService::class)->activeBatchId($request->user());

        SchoolClass::query()->create($data);
        $this->assignCoordinatorToProgram($data, $request->user());
        session([ProgramContextService::SESSION_KEY => $data['program_batch_id']]);

        return redirect()->route('admin.classes.index')->with('success', 'Kelompok/angkatan berhasil dibuat.');
    }

    public function edit(SchoolClass $schoolClass): View
    {
        $this->authorize('update', $schoolClass);

        return view('admin.classes.edit', ['schoolClass' => $schoolClass, ...$this->formData()]);
    }

    public function update(SchoolClassRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $data = $request->validated();
        $data['program_batch_id'] = $data['program_batch_id']
            ?? $schoolClass->program_batch_id
            ?? app(ProgramContextService::class)->activeBatchId($request->user());

        $schoolClass->update($data);
        $this->assignCoordinatorToProgram($data, $request->user());
        session([ProgramContextService::SESSION_KEY => $data['program_batch_id']]);

        return redirect()->route('admin.classes.index')->with('success', 'Kelompok/angkatan berhasil diperbarui.');
    }

    public function destroy(SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorize('delete', $schoolClass);

        if (
            $schoolClass->studentProfiles()->exists()
            || $schoolClass->classMemberships()->exists()
            || $schoolClass->registrationCodes()->exists()
        ) {
            return back()->withErrors(['school_class' => 'Kelompok yang memiliki anggota tidak dapat dihapus.']);
        }

        $schoolClass->delete();

        return redirect()->route('admin.classes.index')->with('success', 'Kelompok/angkatan berhasil dihapus.');
    }

    private function formData(): array
    {
        $programContext = app(ProgramContextService::class);
        $activeBatch = $programContext->activeBatch(request()->user());
        $activeBatchId = $activeBatch?->id;

        return [
            'academicYears' => $programContext->academicYears(request()->user(), ['id', 'name', 'is_active']),
            'activeBatch' => $activeBatch,
            'activeBatchId' => $activeBatchId,
            'availableBatches' => $programContext->availableBatches(request()->user()),
            'groupLabel' => $programContext->groupLabel(request()->user()),
            'periodLabel' => $programContext->periodLabel(request()->user()),
            'teachers' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('slug', [RoleSlug::Teacher->value, RoleSlug::Coach->value]))
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assignCoordinatorToProgram(array $data, User $actor): void
    {
        if (empty($data['homeroom_teacher_id']) || empty($data['program_batch_id'])) {
            return;
        }

        $coordinator = User::query()->find($data['homeroom_teacher_id']);

        if (! $coordinator?->hasAnyRole([RoleSlug::Teacher, RoleSlug::Coach])) {
            return;
        }

        $coordinator->assignedProgramBatches()->syncWithoutDetaching([
            (int) $data['program_batch_id'] => ['assigned_by' => $actor->id],
        ]);
    }
}
