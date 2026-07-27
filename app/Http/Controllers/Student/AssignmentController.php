<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmissionDraftRequest;
use App\Models\Assignment;
use App\Services\ProgramContextService;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Assignment::class);
        $student = $request->user();
        $activeMembership = app(ProgramContextService::class)->studentActiveMembership($student);
        $programBatchId = $activeMembership?->program_batch_id
            ?? $student->studentProfile?->program_batch_id
            ?? $student->classMemberships()->where('status', 'active')->value('program_batch_id');
        $classIds = $student->classMemberships()->where('status', 'active')->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->pluck('class_id');
        $assignments = Assignment::query()->whereIn('class_id', $classIds)->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('is_published', true)->where(fn ($q) => $q->whereNull('available_from')->orWhere('available_from', '<=', now()))->with(['learningSession:id,session_number,title', 'submissions' => fn ($q) => $q->where('user_id', $student->id)])->orderBy('due_at')->paginate(12);

        return view('student.assignments.index', compact('assignments'));
    }

    public function show(Request $request, Assignment $assignment): View
    {
        $this->authorize('view', $assignment);
        $assignment->load(['learningSession', 'questions']);
        $submission = $assignment->submissions()->where('user_id', $request->user()->id)->with(['versions.files', 'versions.answers'])->first();

        return view('student.assignments.show', compact('assignment', 'submission'));
    }

    public function save(SubmissionDraftRequest $request, Assignment $assignment, SubmissionService $service): RedirectResponse
    {
        $submission = $service->save($assignment, $request->user(), $request->validated());

        return redirect()->route('student.assignments.show', $assignment)->with('success', $request->input('action') === 'submit' ? 'Tugas berhasil dikirim.' : 'Draf tugas berhasil disimpan.');
    }
}
