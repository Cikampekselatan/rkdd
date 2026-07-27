<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\RemedialStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\GradeSubmissionRequest;
use App\Models\Grade;
use App\Models\Submission;
use App\Services\GradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function edit(Submission $submission): View
    {
        $this->authorize('view', $submission);
        abort_unless($submission->assignment->rubric_id, 422, 'Tugas belum memiliki rubrik.');
        $submission->load(['student:id,name,email', 'assignment.rubric.criteria.levels', 'assignment.questions', 'versions.files', 'versions.answers.question', 'scores', 'grade.audits.actor:id,name']);

        return view('teacher.grades.edit', ['submission' => $submission, 'grade' => $submission->grade, 'remedialStatuses' => RemedialStatus::cases()]);
    }

    public function update(GradeSubmissionRequest $request, Submission $submission, GradeService $service): RedirectResponse
    {
        $grade = $service->save($submission, $request->validated(), $request->user());

        return redirect()->route('teacher.grades.edit', $submission)->with('success', $grade->is_published ? 'Nilai berhasil dipublikasikan.' : 'Penilaian berhasil disimpan.');
    }

    public function completeRemedial(Grade $grade, GradeService $service): RedirectResponse
    {
        $this->authorize('completeRemedial', $grade);
        $service->completeRemedial($grade, request()->user());

        return back()->with('success', 'Remedial ditandai selesai.');
    }
}
