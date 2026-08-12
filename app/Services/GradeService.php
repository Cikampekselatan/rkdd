<?php

namespace App\Services;

use App\Enums\RemedialStatus;
use App\Enums\SubmissionStatus;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SkuadActivityNotification;
use Illuminate\Support\Facades\DB;
use Throwable;

class GradeService
{
    public function __construct(private readonly SubmissionService $submissions) {}

    public function save(Submission $submission, array $data, User $actor): Grade
    {
        $publishedNow = false;
        $grade = DB::transaction(function () use ($submission, $data, $actor, &$publishedNow) {
            $submission->load('assignment.rubric.criteria.levels');
            $rubric = $submission->assignment->rubric;
            $grade = Grade::firstOrNew(['submission_id' => $submission->id]);
            $wasPublished = $grade->exists && $grade->is_published;
            $before = $grade->exists ? $grade->toArray() : null;
            $total = 0;
            foreach ($data['scores'] as $item) {
                $criterion = $rubric->criteria->firstWhere('id', (int) $item['criterion_id']);
                $level = $criterion->levels->firstWhere('level', (int) $item['level']);
                $weighted = round(((int) $item['level'] / 4) * (float) $criterion->weight, 2);
                $total += $weighted;
                $submission->scores()->updateOrCreate(['rubric_criterion_id' => $criterion->id], ['rubric_level_id' => $level->id, 'level' => $item['level'], 'weight' => $criterion->weight, 'weighted_score' => $weighted, 'teacher_note' => $item['teacher_note'] ?? null]);
            }$action = $data['action'];
            $publish = $action === 'publish';
            $achievement = max(1, min(4, (int) ceil($total / 25)));
            $grade->fill(['rubric_id' => $rubric->id, 'total_score' => round($total, 2), 'achievement_level' => $achievement, 'feedback' => $data['feedback'] ?? null, 'private_note' => $data['private_note'] ?? null, 'graded_by' => $actor->id, 'is_published' => $publish, 'published_at' => $publish ? now() : null, 'published_by' => $publish ? $actor->id : null, 'remedial_status' => $data['remedial_status'], 'remedial_note' => $data['remedial_note'] ?? null, 'remedial_due_at' => $data['remedial_due_at'] ?? null])->save();
            if ($publish) {
                $submission->update(['status' => SubmissionStatus::Graded]);
                $publishedNow = ! $wasPublished;
            }if ($action === 'revision') {
                $this->submissions->requestRevision($submission, $data['revision_note'], $actor);
            }$grade->audits()->create(['user_id' => $actor->id, 'event' => $action === 'publish' ? 'published' : ($action === 'revision' ? 'revision_requested' : 'saved'), 'before' => $before, 'after' => $grade->fresh()->toArray()]);

            return $grade->refresh()->load('scores.criterion', 'scores.rubricLevel');
        });
        if ($publishedNow) {
            try {
                $this->notifyPublished($grade);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $grade;
    }

    public function submitRemedial(Grade $grade, string $response, User $student): Grade
    {
        return DB::transaction(function () use ($grade, $response, $student) {
            $before = $grade->toArray();
            $grade->update(['remedial_status' => RemedialStatus::Submitted, 'remedial_response' => $response, 'remedial_submitted_at' => now()]);
            $grade->audits()->create(['user_id' => $student->id, 'event' => 'remedial_submitted', 'before' => $before, 'after' => $grade->fresh()->toArray()]);

            return $grade->refresh();
        });
    }

    public function completeRemedial(Grade $grade, User $actor): Grade
    {
        $before = $grade->toArray();
        $grade->update(['remedial_status' => RemedialStatus::Completed]);
        $grade->audits()->create(['user_id' => $actor->id, 'event' => 'remedial_completed', 'before' => $before, 'after' => $grade->fresh()->toArray()]);
        $grade->loadMissing('submission.student', 'submission.assignment');
        $grade->submission->student?->notify(new SkuadActivityNotification(
            'remedial_completed',
            'Remedial selesai',
            route('student.grades.show', $grade),
            $grade->submission->assignment->title,
            ['grade_id' => $grade->id, 'submission_id' => $grade->submission_id, ...AnnouncementService::programMeta($grade->submission->assignment->program_batch_id)],
        ));

        return $grade->refresh();
    }

    private function notifyPublished(Grade $grade): void
    {
        $grade->loadMissing('submission.student', 'submission.assignment');
        $grade->submission->student?->notify(new SkuadActivityNotification(
            'grade',
            'Nilai baru dipublikasikan',
            route('student.grades.show', $grade),
            $grade->submission->assignment->title.' · Nilai '.$grade->total_score,
            ['grade_id' => $grade->id, 'submission_id' => $grade->submission_id, ...AnnouncementService::programMeta($grade->submission->assignment->program_batch_id)],
        ));
    }
}
