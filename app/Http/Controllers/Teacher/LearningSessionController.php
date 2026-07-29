<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Learning\PublishLearningSession;
use App\Enums\LearningSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\LearningSessionRequest;
use App\Models\LearningMaterial;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LearningSessionController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', LearningSession::class);

        return view('teacher.learning.sessions.create', $this->formData());
    }

    public function store(LearningSessionRequest $request): RedirectResponse
    {
        $session = DB::transaction(function () use ($request): LearningSession {
            $attributes = [
                ...$request->validated(),
                'program_batch_id' => LearningModule::query()->whereKey($request->integer('learning_module_id'))->value('program_batch_id') ?? app(ProgramContextService::class)->activeBatchId($request->user()),
                'semester' => (int) $request->integer('session_number') <= 15 ? 1 : 2,
                'slug' => $request->integer('session_number').'-'.Str::slug($request->string('title')->toString()),
                'published_at' => null,
                'published_by' => null,
                'updated_by' => $request->user()->id,
            ];
            $session = LearningSession::withTrashed()
                ->where('academic_year_id', $request->integer('academic_year_id'))
                ->when($attributes['program_batch_id'], fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('session_number', $request->integer('session_number'))
                ->first();

            if ($session?->trashed()) {
                LearningMaterial::withTrashed()->where('learning_session_id', $session->id)->forceDelete();
                $session->forceFill($attributes)->save();
                $session->restore();

                return $session->refresh();
            }

            return LearningSession::query()->create([
                ...$attributes,
                'created_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('teacher.learning.sessions.edit', $session)->with('success', 'Pertemuan berhasil dibuat. Tambahkan materi sebelum publikasi.');
    }

    public function edit(LearningSession $learningSession): View
    {
        $this->authorize('update', $learningSession);
        $learningSession->load(['module.academicYear', 'materials'])->loadCount('progressRecords');

        return view('teacher.learning.sessions.edit', [
            'learningSession' => $learningSession,
            ...$this->formData(),
        ]);
    }

    public function update(LearningSessionRequest $request, LearningSession $learningSession): RedirectResponse
    {
        $learningSession->update([
            ...$request->validated(),
            'program_batch_id' => LearningModule::query()->whereKey($request->integer('learning_module_id'))->value('program_batch_id') ?? $learningSession->program_batch_id ?? app(ProgramContextService::class)->activeBatchId($request->user()),
            'semester' => (int) $request->integer('session_number') <= 15 ? 1 : 2,
            'slug' => $request->integer('session_number').'-'.Str::slug($request->string('title')->toString()),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('teacher.learning.sessions.edit', $learningSession)->with('success', 'Pertemuan berhasil diperbarui.');
    }

    public function preview(LearningSession $learningSession): View
    {
        $this->authorize('preview', $learningSession);
        $learningSession->load(['module.academicYear', 'materials']);

        return view('teacher.learning.preview', compact('learningSession'));
    }

    public function publish(LearningSession $learningSession, PublishLearningSession $publish): RedirectResponse
    {
        $this->authorize('publish', $learningSession);

        $publish->execute($learningSession, request()->user());

        return back()->with('success', 'Pertemuan berhasil dipublikasikan kepada siswa.');
    }

    public function destroy(LearningSession $learningSession): RedirectResponse
    {
        $this->authorize('delete', $learningSession);

        if ($learningSession->progressRecords()->exists()) {
            return back()->withErrors(['learning_session' => 'Pertemuan yang sudah memiliki progress siswa tidak dapat dihapus.']);
        }

        if (! in_array($learningSession->status, [
            LearningSessionStatus::Draft,
            LearningSessionStatus::Scheduled,
            LearningSessionStatus::Archived,
        ], true)) {
            return back()->withErrors(['learning_session' => 'Publikasi aktif harus diubah menjadi arsip sebelum dapat dihapus.']);
        }

        DB::transaction(function () use ($learningSession): void {
            $learningSession->materials()->delete();
            $learningSession->delete();
        });

        return redirect()->route('teacher.learning.index')->with('success', 'Pertemuan berhasil dihapus dari kurikulum. Nomornya dapat digunakan kembali.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        $programContext = app(ProgramContextService::class);
        $activeBatchId = $programContext->activeBatchId(request()->user());

        return [
            'academicYears' => $programContext->academicYears(request()->user(), ['id', 'name']),
            'modules' => LearningModule::query()->with('academicYear:id,name')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('academic_year_id')->orderBy('module_number')->get(),
        ];
    }
}
