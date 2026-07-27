<?php

namespace App\Http\Controllers\Student;

use App\Enums\LearningSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateLearningProgressRequest;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Services\ProgramContextService;
use App\Services\StudentLearningProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LearningSession::class);
        $student = $request->user()->loadMissing('studentProfile.schoolClass');
        $programContext = app(ProgramContextService::class);
        $activeMembership = $programContext->studentActiveMembership($student);
        $academicYearId = $activeMembership?->academic_year_id ?? $student->studentProfile?->schoolClass?->academic_year_id;
        $programBatchId = $activeMembership?->program_batch_id
            ?? $student->studentProfile?->program_batch_id
            ?? $student->classMemberships()->where('status', 'active')->value('program_batch_id');

        abort_unless($academicYearId, 404);

        $modules = LearningModule::query()
            ->where('academic_year_id', $academicYearId)
            ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('is_active', true)
            ->whereHas('sessions', fn ($query) => $query->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->whereIn('status', LearningSessionStatus::studentVisibleValues()))
            ->with(['sessions' => function ($query) use ($programBatchId, $student): void {
                $query->whereIn('status', LearningSessionStatus::studentVisibleValues())
                    ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                    ->with(['materials', 'progressRecords' => fn ($progress) => $progress->where('user_id', $student->id)]);
            }])
            ->orderBy('sort_order')
            ->get();

        return view('student.learning.index', [
            'modules' => $modules,
            'activeBatch' => $programContext->activeBatch($student),
            'publishedSessionCount' => $modules->sum(fn (LearningModule $module): int => $module->sessions->count()),
        ]);
    }

    public function show(Request $request, LearningSession $learningSession, StudentLearningProgressService $progressService): View
    {
        $this->authorize('view', $learningSession);
        $learningSession->load(['module.academicYear', 'materials']);
        $progress = $progressService->recordOpened($request->user(), $learningSession);

        return view('student.learning.show', compact('learningSession', 'progress'));
    }

    public function updateProgress(UpdateLearningProgressRequest $request, LearningSession $learningSession, StudentLearningProgressService $progressService): RedirectResponse
    {
        $this->authorize('view', $learningSession);
        $progressService->completeComponent($request->user(), $learningSession, $request->validated('component'));

        return back()->with('success', 'Progress belajar tersimpan.');
    }
}
