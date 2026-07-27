<?php

namespace App\Services;

use App\Enums\RoleSlug;
use App\Enums\TeacherActivityStatus;
use App\Models\TeacherActivityLog;
use App\Models\User;
use App\Notifications\SkuadActivityNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeacherActivityLogService
{
    /** @param array<string, mixed> $data */
    public function save(?TeacherActivityLog $log, array $data, ?UploadedFile $signature, User $actor): TeacherActivityLog
    {
        $submittedNow = false;
        $savedLog = DB::transaction(function () use ($actor, $data, $log, $signature, &$submittedNow): TeacherActivityLog {
            $submit = (bool) ($data['submit_now'] ?? false);
            $drawnSignature = $data['signature_drawn'] ?? null;
            unset($data['submit_now'], $data['signature'], $data['signature_drawn']);
            $oldPath = $log?->signature_path;
            $signatureData = $this->signatureData($signature, is_string($drawnSignature) ? $drawnSignature : null, $actor);
            if (! $log) {
                $data['program_batch_id'] ??= app(ProgramContextService::class)->activeBatchId($actor);
                $number = ((int) TeacherActivityLog::query()->where('academic_year_id', $data['academic_year_id'])->when($data['program_batch_id'] ?? null, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('teacher_id', $actor->id)->lockForUpdate()->max('log_number')) + 1;
                $log = TeacherActivityLog::query()->create([...$data, ...$signatureData, 'teacher_id' => $actor->id, 'log_number' => $number, 'status' => $submit ? TeacherActivityStatus::Submitted : TeacherActivityStatus::Draft, 'submitted_at' => $submit ? now() : null]);
                $submittedNow = $submit;
                $this->audit($log, $actor, $submit ? 'created_and_submitted' : 'created', null, $log->status);
            } else {
                $oldStatus = $log->status;
                $log->update([...$data, ...$signatureData, 'program_batch_id' => $log->program_batch_id ?? $data['program_batch_id'] ?? app(ProgramContextService::class)->activeBatchId($actor), 'status' => $submit ? TeacherActivityStatus::Submitted : $log->status, 'submitted_at' => $submit ? now() : $log->submitted_at, 'verified_by' => null, 'verified_at' => null, 'rejection_note' => $submit ? null : $log->rejection_note]);
                $submittedNow = $submit && $oldStatus !== TeacherActivityStatus::Submitted;
                $this->audit($log, $actor, $submit ? 'resubmitted' : 'updated', $oldStatus, $log->status);
            }
            if ($signatureData && $oldPath && $oldPath !== $log->signature_path) {
                Storage::disk('local')->delete($oldPath);
            }

            return $log->refresh();
        });
        if ($submittedNow) {
            $this->notifyTeachersToReview($savedLog, $actor);
        }

        return $savedLog;
    }

    /**
     * @return array{signature_path: string, signature_original_name: string}|array{}
     */
    private function signatureData(?UploadedFile $signature, ?string $drawnSignature, User $actor): array
    {
        if ($signature) {
            return ['signature_path' => $signature->store('teacher-signatures/'.$actor->id, 'local'), 'signature_original_name' => $signature->getClientOriginalName()];
        }

        if (! $drawnSignature) {
            return [];
        }

        $encoded = substr($drawnSignature, strlen('data:image/png;base64,'));
        $binary = base64_decode($encoded, true);

        if ($binary === false) {
            return [];
        }

        $path = 'teacher-signatures/'.$actor->id.'/drawn-signature-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.png';
        Storage::disk('local')->put($path, $binary);

        return ['signature_path' => $path, 'signature_original_name' => 'tanda-tangan-langsung.png'];
    }

    public function review(TeacherActivityLog $log, string $decision, ?string $note, ?UploadedFile $reviewerSignature, ?string $drawnReviewerSignature, User $actor): TeacherActivityLog
    {
        $log = DB::transaction(function () use ($actor, $decision, $drawnReviewerSignature, $log, $note, $reviewerSignature): TeacherActivityLog {
            $old = $log->status;
            $status = TeacherActivityStatus::from($decision);
            $signatureData = $status === TeacherActivityStatus::Verified ? $this->reviewerSignatureData($reviewerSignature, is_string($drawnReviewerSignature) ? $drawnReviewerSignature : null, $actor) : [];
            $oldPath = $log->reviewer_signature_path;
            $log->update([...$signatureData, 'status' => $status, 'verified_by' => $actor->id, 'verified_at' => $status === TeacherActivityStatus::Verified ? now() : null, 'rejection_note' => $status === TeacherActivityStatus::Rejected ? $note : null]);
            if ($signatureData && $oldPath && $oldPath !== $log->reviewer_signature_path) {
                Storage::disk('local')->delete($oldPath);
            }
            $this->audit($log, $actor, $decision, $old, $status, ['note' => $note]);

            return $log->refresh();
        });
        $log->loadMissing('teacher');
        $log->teacher?->notify(new SkuadActivityNotification(
            $decision === 'verified' ? 'teacher_log_verified' : 'teacher_log_rejected',
            $decision === 'verified' ? 'Absen pengajar sudah ditandatangani' : 'Absen pengajar perlu diperbaiki',
            route('activity-logs.show', $log),
            $log->activity_date->translatedFormat('d F Y').' · '.$log->material,
            ['teacher_activity_log_id' => $log->id, ...AnnouncementService::programMeta($log->program_batch_id)],
        ));
        if ($decision === 'verified') {
            $this->notifyLeadershipVerified($log);
        }

        return $log;
    }

    private function notifyTeachersToReview(TeacherActivityLog $log, User $actor): void
    {
        User::query()
            ->whereKeyNot($actor->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Teacher->value))
            ->get()
            ->each
            ->notify(new SkuadActivityNotification(
                'teacher_log_review',
                'Absen pengajar menunggu tanda tangan',
                route('activity-logs.show', $log),
                $actor->name.' · '.$log->activity_date->translatedFormat('d F Y'),
                ['teacher_activity_log_id' => $log->id, ...AnnouncementService::programMeta($log->program_batch_id)],
            ));
    }

    private function notifyLeadershipVerified(TeacherActivityLog $log): void
    {
        User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', [RoleSlug::SuperAdmin->value, RoleSlug::Admin->value, RoleSlug::Principal->value]))
            ->get()
            ->each
            ->notify(new SkuadActivityNotification(
                'teacher_log_verified',
                'Absen pengajar terverifikasi',
                route('activity-logs.show', $log),
                $log->teacher?->name.' · '.$log->activity_date->translatedFormat('d F Y'),
                ['teacher_activity_log_id' => $log->id, ...AnnouncementService::programMeta($log->program_batch_id)],
            ));
    }

    /**
     * @return array{reviewer_signature_path: string, reviewer_signature_original_name: string}|array{}
     */
    private function reviewerSignatureData(?UploadedFile $signature, ?string $drawnSignature, User $actor): array
    {
        if ($signature) {
            return ['reviewer_signature_path' => $signature->store('teacher-signatures/reviewer/'.$actor->id, 'local'), 'reviewer_signature_original_name' => $signature->getClientOriginalName()];
        }

        if (! $drawnSignature) {
            return [];
        }

        $encoded = substr($drawnSignature, strlen('data:image/png;base64,'));
        $binary = base64_decode($encoded, true);

        if ($binary === false) {
            return [];
        }

        $path = 'teacher-signatures/reviewer/'.$actor->id.'/drawn-signature-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.png';
        Storage::disk('local')->put($path, $binary);

        return ['reviewer_signature_path' => $path, 'reviewer_signature_original_name' => 'tanda-tangan-pembina-langsung.png'];
    }

    private function audit(TeacherActivityLog $log, User $actor, string $event, ?TeacherActivityStatus $old, TeacherActivityStatus $new, ?array $context = null): void
    {
        $log->audits()->create(['user_id' => $actor->id, 'event' => $event, 'old_status' => $old, 'new_status' => $new, 'context' => $context]);
    }
}
