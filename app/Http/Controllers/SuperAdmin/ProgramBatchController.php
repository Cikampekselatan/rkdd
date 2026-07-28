<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\ProgramBatchRequest;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgramBatchController extends Controller
{
    public function index(): View
    {
        return view('super-admin.program-batches.index', [
            'batches' => ProgramBatch::query()
                ->with(['program', 'institution'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.program-batches.create', [
            ...$this->formData(),
            'programBatch' => $this->draftFromRequest(),
        ]);
    }

    public function store(ProgramBatchRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $batch = ProgramBatch::query()->create($request->validated());
            $this->syncActiveBatchForProgram($batch);
        });

        return redirect()->route('super-admin.program-batches.index')->with('success', 'Batch/periode program berhasil dibuat.');
    }

    public function edit(ProgramBatch $programBatch): View
    {
        return view('super-admin.program-batches.edit', [
            'programBatch' => $programBatch,
            ...$this->formData(),
        ]);
    }

    public function update(ProgramBatchRequest $request, ProgramBatch $programBatch): RedirectResponse
    {
        if (! $request->boolean('is_active') && $this->isLastActiveBatchForProgram($programBatch)) {
            return back()->withErrors(['is_active' => 'Minimal harus ada satu batch/periode aktif untuk program ini. Aktifkan batch lain terlebih dahulu.']);
        }

        DB::transaction(function () use ($request, $programBatch): void {
            $programBatch->update($request->validated());
            $this->syncActiveBatchForProgram($programBatch);
        });

        return redirect()->route('super-admin.program-batches.index')->with('success', 'Batch/periode program berhasil diperbarui.');
    }

    public function destroy(ProgramBatch $programBatch): RedirectResponse
    {
        $programBatch->delete();

        return redirect()->route('super-admin.program-batches.index')->with('success', 'Batch/periode program berhasil dihapus.');
    }

    private function formData(): array
    {
        return [
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'institutions' => Institution::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function draftFromRequest(): ProgramBatch
    {
        $program = Program::query()
            ->where('is_active', true)
            ->find(request()->integer('program_id'));
        $institution = Institution::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        $academicYear = AcademicYear::query()
            ->where('is_active', true)
            ->first();

        return new ProgramBatch([
            'program_id' => $program?->id,
            'institution_id' => $institution?->id,
            'name' => $program && $institution && $academicYear
                ? "{$program->name} {$institution->name} {$academicYear->name}"
                : '',
            'period_label' => $academicYear?->name ?? '',
            'audience_type' => $institution?->type === 'sekolah' ? 'school' : 'village',
            'participant_label' => $institution?->type === 'sekolah' ? 'Siswa' : 'Peserta',
            'is_active' => true,
        ]);
    }

    private function syncActiveBatchForProgram(ProgramBatch $batch): void
    {
        if (! $batch->is_active) {
            return;
        }

        ProgramBatch::query()
            ->where('program_id', $batch->program_id)
            ->whereKeyNot($batch->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function isLastActiveBatchForProgram(ProgramBatch $batch): bool
    {
        if (! $batch->is_active) {
            return false;
        }

        return ProgramBatch::query()
            ->where('program_id', $batch->program_id)
            ->where('is_active', true)
            ->whereKeyNot($batch->id)
            ->doesntExist();
    }
}
