<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\LearningModuleRequest;
use App\Models\AcademicYear;
use App\Models\LearningModule;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LearningModuleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LearningModule::class);
        $academicYearId = $request->integer('academic_year_id');
        $programContext = app(ProgramContextService::class);
        $activeBatchId = $programContext->activeBatchId($request->user());

        return view('teacher.learning.index', [
            'modules' => LearningModule::query()
                ->with(['academicYear:id,name', 'sessions.materials'])
                ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
                ->orderBy('academic_year_id')
                ->orderBy('sort_order')
                ->paginate(15)
                ->withQueryString(),
            'academicYears' => AcademicYear::query()->orderByDesc('starts_on')->get(['id', 'name']),
            'selectedAcademicYear' => $academicYearId,
            'activeBatch' => $programContext->activeBatch($request->user()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LearningModule::class);

        return view('teacher.learning.modules.create', $this->formData());
    }

    public function store(LearningModuleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
            $attributes = [
                ...$request->validated(),
                'program_batch_id' => $activeBatchId,
                'slug' => Str::slug($request->string('title')->toString()).'-'.$request->integer('module_number'),
                'updated_by' => $request->user()->id,
            ];
            $module = LearningModule::withTrashed()
                ->where('academic_year_id', $request->integer('academic_year_id'))
                ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('module_number', $request->integer('module_number'))
                ->first();

            if ($module?->trashed()) {
                $module->forceFill($attributes)->save();
                $module->restore();

                return;
            }

            LearningModule::query()->create([
                ...$attributes,
                'created_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('teacher.learning.index')->with('success', 'Modul berhasil ditambahkan ke kurikulum.');
    }

    public function edit(LearningModule $learningModule): View
    {
        $this->authorize('update', $learningModule);

        return view('teacher.learning.modules.edit', [
            'learningModule' => $learningModule,
            ...$this->formData(),
        ]);
    }

    public function update(LearningModuleRequest $request, LearningModule $learningModule): RedirectResponse
    {
        $learningModule->update([
            ...$request->validated(),
            'program_batch_id' => $learningModule->program_batch_id ?? app(ProgramContextService::class)->activeBatchId($request->user()),
            'slug' => Str::slug($request->string('title')->toString()).'-'.$request->integer('module_number'),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('teacher.learning.index')->with('success', 'Modul berhasil diperbarui.');
    }

    public function destroy(LearningModule $learningModule): RedirectResponse
    {
        $this->authorize('delete', $learningModule);

        if ($learningModule->sessions()->exists()) {
            return back()->withErrors(['learning_module' => 'Modul yang memiliki pertemuan tidak dapat dihapus.']);
        }

        $learningModule->delete();

        return redirect()->route('teacher.learning.index')->with('success', 'Modul berhasil diarsipkan.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'academicYears' => AcademicYear::query()->orderByDesc('starts_on')->get(['id', 'name']),
        ];
    }
}
