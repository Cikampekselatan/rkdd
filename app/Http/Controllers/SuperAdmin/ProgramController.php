<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\ProgramRequest;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Services\PortfolioWorkTypeOptionService;
use App\Services\ProgramAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('super-admin.programs.index', [
            'institutions' => $this->activeInstitutions(),
            'programs' => Program::query()
                ->with(['firstBatch.institution', 'batches' => fn ($query) => $query->with('institution')->latest()->limit(4)])
                ->withCount('batches')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.programs.create', [
            'institutions' => $this->activeInstitutions(),
            'program' => new Program,
        ]);
    }

    public function store(ProgramRequest $request, ProgramAssetService $assets): RedirectResponse
    {
        $program = Program::query()->create($request->safe()->except(['institution_id', 'logo', 'banner']));
        $this->ensureDefaultBatch($program, $request->validated('institution_id'));
        app(PortfolioWorkTypeOptionService::class)->ensureDefaults($program);

        if ($request->hasFile('logo')) {
            $assets->storeLogo($program, $request->file('logo'));
        }

        if ($request->hasFile('banner')) {
            $assets->storeBanner($program, $request->file('banner'));
        }

        return redirect()->route('super-admin.programs.index')->with('success', 'Program RKDD berhasil dibuat.');
    }

    public function edit(Program $program): View
    {
        $program->load('firstBatch.institution');

        return view('super-admin.programs.edit', [
            'institutions' => $this->activeInstitutions(),
            'program' => $program,
        ]);
    }

    public function update(ProgramRequest $request, Program $program, ProgramAssetService $assets): RedirectResponse
    {
        $program->update($request->safe()->except(['institution_id', 'logo', 'banner']));
        $this->syncPrimaryInstitution($program, $request->validated('institution_id'));

        if ($request->hasFile('logo')) {
            $assets->storeLogo($program, $request->file('logo'));
        }

        if ($request->hasFile('banner')) {
            $assets->storeBanner($program, $request->file('banner'));
        }

        return redirect()->route('super-admin.programs.index')->with('success', 'Program RKDD berhasil diperbarui.');
    }

    public function destroy(Program $program, ProgramAssetService $assets): RedirectResponse
    {
        if ($program->batches()->exists()) {
            return back()->withErrors(['program' => 'Program yang sudah memiliki batch/periode tidak dapat dihapus. Nonaktifkan program jika tidak digunakan.']);
        }

        $assets->deleteAssets($program);
        $program->delete();

        return redirect()->route('super-admin.programs.index')->with('success', 'Program RKDD berhasil dihapus.');
    }

    private function ensureDefaultBatch(Program $program, ?int $institutionId = null): void
    {
        if (! $program->is_active || $program->batches()->exists()) {
            return;
        }

        $academicYear = AcademicYear::query()->where('is_active', true)->first()
            ?? AcademicYear::query()->orderByDesc('starts_on')->first();
        $institution = $this->resolveDefaultInstitution($institutionId);

        $periodLabel = $academicYear?->name ?? 'Batch 1';
        $audienceType = $institution->type === 'sekolah' ? 'school' : 'community';
        $participantLabel = $audienceType === 'school' ? 'Siswa' : 'Peserta';

        ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => "{$program->name} {$institution->name} {$periodLabel}",
            'slug' => $this->uniqueBatchSlug($program, $institution, $periodLabel),
            'period_label' => $periodLabel,
            'starts_on' => $academicYear?->starts_on,
            'ends_on' => $academicYear?->ends_on,
            'audience_type' => $audienceType,
            'participant_label' => $participantLabel,
            'is_active' => true,
        ]);
    }

    private function uniqueBatchSlug(Program $program, Institution $institution, string $periodLabel): string
    {
        $base = Str::slug("{$program->slug}-{$institution->slug}-{$periodLabel}") ?: 'program-batch';
        $slug = $base;
        $counter = 2;

        while (ProgramBatch::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function resolveDefaultInstitution(?int $institutionId): Institution
    {
        if ($institutionId) {
            $institution = Institution::query()
                ->whereKey($institutionId)
                ->where('is_active', true)
                ->first();

            if ($institution) {
                return $institution;
            }
        }

        return Institution::query()->where('is_active', true)->orderBy('id')->first()
            ?? Institution::query()->create([
                'name' => 'RKDD Cikampek Selatan',
                'slug' => 'rkdd-cikampek-selatan',
                'type' => 'rkdd',
                'address' => 'Desa Cikampek Selatan',
                'description' => 'Lembaga default untuk program RKDD.',
                'is_active' => true,
            ]);
    }

    private function syncPrimaryInstitution(Program $program, ?int $institutionId): void
    {
        $batch = $program->firstBatch()->first();

        if (! $batch) {
            $this->ensureDefaultBatch($program, $institutionId);

            return;
        }

        if (! $institutionId) {
            return;
        }

        $institution = $this->resolveDefaultInstitution($institutionId);
        $batch->forceFill([
            'institution_id' => $institution->id,
            'name' => "{$program->name} {$institution->name} {$batch->period_label}",
            'audience_type' => $institution->type === 'sekolah' ? 'school' : 'community',
            'participant_label' => $institution->type === 'sekolah' ? 'Siswa' : 'Peserta',
        ])->save();
    }

    private function activeInstitutions()
    {
        return Institution::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
    }
}
