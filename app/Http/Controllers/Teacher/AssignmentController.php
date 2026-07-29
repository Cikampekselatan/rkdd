<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AssignmentRequest;
use App\Http\Requests\Teacher\RequestSubmissionRevisionRequest;
use App\Models\Assignment;
use App\Models\LearningSession;
use App\Models\Rubric;
use App\Models\SchoolClass;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SkuadActivityNotification;
use App\Services\AnnouncementService;
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
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
        $assignments = Assignment::query()->with(['learningSession:id,session_number,title', 'schoolClass:id,name'])->withCount('submissions')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->latest('due_at')->paginate(12);

        return view('teacher.assignments.index', compact('assignments'));
    }

    public function create(): View
    {
        $this->authorize('create', Assignment::class);

        return view('teacher.assignments.form', ['assignment' => new Assignment, ...$this->formData()]);
    }

    public function store(AssignmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['program_batch_id'] = SchoolClass::query()->whereKey($request->integer('class_id'))->value('program_batch_id') ?? app(ProgramContextService::class)->activeBatchId($request->user());
        $questions = $data['questions'] ?? [];
        unset($data['questions']);
        $a = Assignment::create([...$data, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $this->syncQuestions($a, $questions);
        $this->notifyPublishedAssignment($a, $request->user());

        return redirect()->route('teacher.assignments.show', $a)->with('success', 'Tugas berhasil dibuat.');
    }

    public function show(Assignment $assignment): View
    {
        $this->authorize('view', $assignment);
        $assignment->load(['learningSession', 'schoolClass', 'questions', 'submissions' => fn ($q) => $q->with('student:id,name,email')->latest('submitted_at')]);

        return view('teacher.assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment): View
    {
        $this->authorize('update', $assignment);
        $assignment->load('questions');

        return view('teacher.assignments.form', ['assignment' => $assignment, ...$this->formData()]);
    }

    public function update(AssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $data = $request->validated();
        $data['program_batch_id'] = SchoolClass::query()->whereKey($request->integer('class_id'))->value('program_batch_id') ?? $assignment->program_batch_id ?? app(ProgramContextService::class)->activeBatchId($request->user());
        $questions = $data['questions'] ?? [];
        unset($data['questions']);
        $wasPublished = $assignment->is_published;
        $assignment->update([...$data, 'updated_by' => $request->user()->id]);
        $this->syncQuestions($assignment, $questions);
        if (! $wasPublished) {
            $this->notifyPublishedAssignment($assignment->refresh(), $request->user());
        }

        return redirect()->route('teacher.assignments.show', $assignment)->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);
        $assignment->delete();

        return redirect()->route('teacher.assignments.index')->with('success', 'Tugas berhasil dihapus.');
    }

    public function submission(Submission $submission): View
    {
        $this->authorize('view', $submission);
        $submission->load(['student:id,name,email', 'assignment.learningSession', 'assignment.questions', 'versions.files', 'versions.answers.question']);

        return view('teacher.assignments.submission', compact('submission'));
    }

    public function review(Submission $submission, SubmissionService $service): RedirectResponse
    {
        $this->authorize('review', $submission);
        $service->startReview($submission);

        return back()->with('success', 'Submission masuk tahap peninjauan.');
    }

    public function revision(RequestSubmissionRevisionRequest $request, Submission $submission, SubmissionService $service): RedirectResponse
    {
        $service->requestRevision($submission, $request->string('revision_note')->toString(), $request->user());

        return back()->with('success', 'Permintaan revisi dikirim kepada siswa.');
    }

    private function formData(): array
    {
        $programContext = app(ProgramContextService::class);
        $activeBatchId = $programContext->activeBatchId(request()->user());

        return ['academicYears' => $programContext->academicYears(request()->user()), 'classes' => SchoolClass::with('academicYear:id,name')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('name')->get(), 'learningSessions' => LearningSession::with('academicYear:id,name')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('academic_year_id')->orderBy('session_number')->get(), 'types' => AssignmentType::cases(), 'questionTypes' => AssignmentQuestionType::cases(), 'rubrics' => Rubric::where('is_active', true)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->withSum('criteria', 'weight')->orderBy('name')->get()];
    }

    private function syncQuestions(Assignment $assignment, array $questions): void
    {
        $assignment->questions()->delete();
        foreach (array_values($questions) as $index => $question) {
            $assignment->questions()->create([
                'sort_order' => $index + 1,
                'prompt' => $question['prompt'],
                'help_text' => $question['help_text'] ?? null,
                'answer_type' => $question['answer_type'],
                'options' => $question['options'] ?? null,
                'is_required' => (bool) ($question['is_required'] ?? false),
            ]);
        }
    }

    private function notifyPublishedAssignment(Assignment $assignment, User $actor): void
    {
        if (! $assignment->is_published) {
            return;
        }

        $students = User::query()
            ->whereKeyNot($actor->id)
            ->where('status', 'active')
            ->whereHas('classMemberships', fn ($query) => $query
                ->where('class_id', $assignment->class_id)
                ->when($assignment->program_batch_id, fn ($membershipQuery, int $batchId) => $membershipQuery->where('program_batch_id', $batchId))
                ->where('status', 'active'))
            ->get();

        $students->each->notify(new SkuadActivityNotification(
            'assignment',
            'Tugas baru: '.$assignment->title,
            route('student.assignments.show', $assignment),
            'Tenggat: '.$assignment->due_at->translatedFormat('d F Y H:i'),
            ['assignment_id' => $assignment->id, ...AnnouncementService::programMeta($assignment->program_batch_id)],
        ));
    }
}
