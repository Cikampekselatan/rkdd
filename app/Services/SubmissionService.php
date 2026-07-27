<?php

namespace App\Services;

use App\Enums\AssignmentType;
use App\Enums\RoleSlug;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionVersion;
use App\Models\User;
use App\Notifications\SkuadActivityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    /** @param array<string,mixed> $data */
    public function save(Assignment $assignment, User $student, array $data): Submission
    {
        $newPaths = [];
        $deletePaths = [];
        try {
            $submitted = false;
            $submission = DB::transaction(function () use ($assignment, $student, $data, &$newPaths, &$deletePaths, &$submitted): Submission {
                $submission = Submission::query()->where('assignment_id', $assignment->id)->where('user_id', $student->id)->lockForUpdate()->first();
                if (! $submission) {
                    $submission = Submission::create(['assignment_id' => $assignment->id, 'user_id' => $student->id]);
                    $version = $submission->versions()->create(['version_number' => 1]);
                } else {
                    $submission->load('versions.files');
                    if (! in_array($submission->status, [SubmissionStatus::Draft, SubmissionStatus::RevisionRequested], true)) {
                        throw ValidationException::withMessages(['submission' => 'Submission sedang terkunci.']);
                    }$version = $submission->versions->firstWhere('version_number', $submission->current_version_number);
                    if ($submission->status === SubmissionStatus::RevisionRequested && $version?->submitted_at) {
                        $number = $submission->current_version_number + 1;
                        $version = $submission->versions()->create(['version_number' => $number]);
                        $submission->update(['current_version_number' => $number]);
                    }
                }
                $version->update(['text_content' => $data['text_content'] ?? null, 'video_url' => $data['video_url'] ?? null, 'external_url' => $data['external_url'] ?? null, 'student_note' => $data['student_note'] ?? null]);
                foreach ($data['answers'] ?? [] as $answer) {
                    $version->answers()->updateOrCreate(
                        ['assignment_question_id' => (int) $answer['question_id']],
                        [
                            'answer_text' => $answer['answer_text'] ?? null,
                            'answer_url' => $answer['answer_url'] ?? null,
                        ],
                    );
                }
                $remove = collect($data['remove_files'] ?? [])->map(fn ($id) => (int) $id);
                $version->files()->whereIn('id', $remove)->get()->each(function (SubmissionFile $f) use (&$deletePaths) {
                    $deletePaths[] = $f->stored_path;
                    $f->delete();
                });
                foreach ($data['files'] ?? [] as $file) {
                    $name = Str::uuid().'.'.$file->guessExtension();
                    $path = $file->storeAs('submissions/'.$assignment->id.'/'.$student->id, $name, 'local');
                    $newPaths[] = $path;
                    $version->files()->create(['original_name' => $file->getClientOriginalName(), 'stored_path' => $path, 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize()]);
                }
                $version->load('files');
                if ($version->files->count() > $assignment->max_files) {
                    throw ValidationException::withMessages(['files' => 'Jumlah file melebihi batas tugas.']);
                }
                if (($data['action'] ?? 'draft') === 'submit') {
                    $this->validateReady($assignment, $version);
                    if (now()->isAfter($assignment->due_at) && ! $assignment->allow_late) {
                        throw ValidationException::withMessages(['submission' => 'Tenggat telah lewat dan pengiriman terlambat tidak diizinkan.']);
                    }$isRevision = $version->version_number > 1;
                    $status = $isRevision ? SubmissionStatus::Resubmitted : (now()->isAfter($assignment->due_at) ? SubmissionStatus::Late : SubmissionStatus::Submitted);
                    $version->update(['submitted_at' => now()]);
                    $submission->update(['status' => $status, 'submitted_at' => now(), 'revision_count' => $isRevision ? $version->version_number - 1 : 0, 'revision_note' => null]);
                    $submitted = true;
                }
                foreach ($deletePaths as $path) {
                    Storage::disk('local')->delete($path);
                }

                return $submission->refresh()->load('versions.files');
            });
            if ($submitted) {
                $this->notifySubmitted($submission, $student);
            }

            return $submission;
        } catch (\Throwable $e) {
            foreach ($newPaths as $path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }
    }

    public function startReview(Submission $submission): void
    {
        $submission->update(['status' => SubmissionStatus::UnderReview, 'last_reviewed_at' => now()]);
    }

    public function requestRevision(Submission $submission, string $note, User $actor): void
    {
        if ($submission->revision_count >= $submission->assignment->max_revisions) {
            throw ValidationException::withMessages(['revision_note' => 'Batas revisi telah tercapai.']);
        }$submission->update(['status' => SubmissionStatus::RevisionRequested, 'revision_note' => $note, 'revision_requested_by' => $actor->id, 'revision_requested_at' => now(), 'last_reviewed_at' => now()]);
        $submission->loadMissing('student', 'assignment');
        $submission->student?->notify(new SkuadActivityNotification(
            'assignment_revision',
            'Revisi diminta: '.$submission->assignment->title,
            route('student.assignments.show', $submission->assignment),
            $note,
            ['assignment_id' => $submission->assignment_id, 'submission_id' => $submission->id, ...AnnouncementService::programMeta($submission->assignment->program_batch_id)],
        ));
    }

    private function notifySubmitted(Submission $submission, User $student): void
    {
        $submission->loadMissing('assignment');
        User::query()
            ->whereKeyNot($student->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', [RoleSlug::Teacher->value, RoleSlug::Coach->value]))
            ->get()
            ->each
            ->notify(new SkuadActivityNotification(
                'submission',
                $student->name.' mengirim tugas',
                route('teacher.submissions.show', $submission),
                $submission->assignment->title,
                ['assignment_id' => $submission->assignment_id, 'submission_id' => $submission->id, ...AnnouncementService::programMeta($submission->assignment->program_batch_id)],
            ));
    }

    private function validateReady(Assignment $a, SubmissionVersion $v): void
    {
        $v->loadMissing('answers');
        if ($a->questions()->exists()) {
            $answers = $v->answers->keyBy('assignment_question_id');
            $allRequiredAnswered = $a->questions()->get()->every(function ($question) use ($answers): bool {
                if (! $question->is_required) {
                    return true;
                }
                $answer = $answers->get($question->id);

                return $answer && (filled($answer->answer_text) || filled($answer->answer_url));
            });
            if (! $allRequiredAnswered) {
                throw ValidationException::withMessages(['submission' => 'Pertanyaan wajib belum lengkap.']);
            }

            return;
        }

        $ok = match ($a->type) {
            AssignmentType::Text,AssignmentType::Reflection => filled($v->text_content),AssignmentType::Document,AssignmentType::Image => $v->files->isNotEmpty(),AssignmentType::VideoLink => filled($v->video_url),AssignmentType::ExternalLink => filled($v->external_url),AssignmentType::Mixed => filled($v->text_content) || filled($v->video_url) || filled($v->external_url) || $v->files->isNotEmpty()
        };
        if (! $ok) {
            throw ValidationException::withMessages(['submission' => 'Isi submission belum memenuhi jenis tugas.']);
        }
    }
}
