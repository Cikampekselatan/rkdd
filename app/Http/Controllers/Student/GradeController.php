<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitRemedialRequest;
use App\Models\Grade;
use App\Models\GroupProjectAssessment;
use App\Models\MonthlyStudentAssessment;
use App\Services\GradeService;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Grade::class);
        $student = $request->user();
        $activeMembership = app(ProgramContextService::class)->studentActiveMembership($student);
        $programBatchId = $activeMembership?->program_batch_id
            ?? $student->studentProfile?->program_batch_id
            ?? $student->classMemberships()->where('status', 'active')->value('program_batch_id');
        $grades = Grade::where('is_published', true)->whereHas('submission', fn ($q) => $q->where('user_id', $student->id)->whereHas('assignment', fn ($assignment) => $assignment->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))))->with('submission.assignment')->latest('published_at')->paginate(12);
        $monthlyAssessments = MonthlyStudentAssessment::query()
            ->where('user_id', $student->id)
            ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('is_published', true)
            ->with(['academicYear', 'schoolClass'])
            ->latest('published_at')
            ->latest('assessed_at')
            ->limit(12)
            ->get();
        $groupAssessments = GroupProjectAssessment::query()
            ->where('is_published', true)
            ->whereHas('groupProject.projectGroup', fn ($query) => $query->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->whereHas('activeMembers', fn ($member) => $member->where('user_id', $student->id)))
            ->with('groupProject.projectGroup')
            ->latest('published_at')
            ->limit(12)
            ->get();

        return view('student.grades.index', compact('grades', 'monthlyAssessments', 'groupAssessments'));
    }

    public function show(Grade $grade): View
    {
        $this->authorize('view', $grade);
        $grade->load(['submission.assignment', 'rubric', 'scores.criterion', 'scores.rubricLevel']);

        return view('student.grades.show', compact('grade'));
    }

    public function monthly(MonthlyStudentAssessment $monthlyStudentAssessment): View
    {
        $this->authorize('view', $monthlyStudentAssessment);
        $monthlyStudentAssessment->load(['academicYear', 'schoolClass', 'assessor']);

        return view('student.grades.monthly-show', [
            'assessment' => $monthlyStudentAssessment,
            'components' => MonthlyStudentAssessment::COMPONENTS,
        ]);
    }

    public function groupProject(Request $request, GroupProjectAssessment $groupProjectAssessment): View
    {
        abort_unless($groupProjectAssessment->is_published, 403);
        $activeMembership = app(ProgramContextService::class)->studentActiveMembership($request->user());
        $programBatchId = $groupProjectAssessment->groupProject->projectGroup->program_batch_id;

        abort_unless($programBatchId === null || $activeMembership?->program_batch_id === $programBatchId, 403);
        abort_unless($groupProjectAssessment->groupProject->projectGroup->activeMembers()->where('user_id', $request->user()->id)->exists(), 403);

        $groupProjectAssessment->load(['groupProject.projectGroup.members.student:id,name,email', 'assessor:id,name']);

        return view('student.grades.group-project-show', ['assessment' => $groupProjectAssessment]);
    }

    public function remedial(SubmitRemedialRequest $request, Grade $grade, GradeService $service): RedirectResponse
    {
        $service->submitRemedial($grade, $request->string('remedial_response')->toString(), $request->user());

        return back()->with('success', 'Jawaban remedial berhasil dikirim.');
    }
}
