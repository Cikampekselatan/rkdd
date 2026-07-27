<?php

namespace App\Services;

use App\Enums\AnnouncementAudience;
use App\Enums\RoleSlug;
use App\Models\Announcement;
use App\Models\LearningSession;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Support\Facades\Notification;

class AnnouncementService
{
    public function save(?Announcement $announcement, array $data, User $actor): Announcement
    {
        $wasPublished = $announcement?->is_published === true;
        $publish = $data['action'] === 'publish';
        unset($data['action']);
        $data['is_published'] = $publish;
        $data['published_at'] = $publish ? ($data['published_at'] ?? now()) : null;
        if ($data['audience'] !== AnnouncementAudience::ClassRoom->value) {
            $data['class_id'] = null;
        }
        if ($data['audience'] !== AnnouncementAudience::Session->value) {
            $data['learning_session_id'] = null;
        }
        $data['program_batch_id'] ??= $this->resolveProgramBatchId($data, $actor);
        $announcement ??= new Announcement(['created_by' => $actor->id]);
        $announcement->fill($data)->save();
        if ($publish && ! $wasPublished && ! $announcement->published_at->isFuture()) {
            Notification::send($this->recipients($announcement, $actor), new AnnouncementPublishedNotification($announcement));
        }

        return $announcement->refresh();
    }

    public function markRead(Announcement $announcement, User $user): void
    {
        $announcement->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);
        $user->unreadNotifications()->where('data->announcement_id', $announcement->id)->update(['read_at' => now()]);
    }

    private function recipients(Announcement $announcement, User $actor)
    {
        $query = User::query()->whereKeyNot($actor->id)->where('status', 'active');
        $programBatchId = $announcement->program_batch_id;
        $scopeStudents = function ($q) use ($programBatchId): void {
            $q->where('status', 'active')
                ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId));
        };

        return match ($announcement->audience) {
            AnnouncementAudience::Students => $query
                ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Student->value))
                ->whereHas('classMemberships', $scopeStudents)
                ->get(),
            AnnouncementAudience::Teachers => $query->whereHas('roles', fn ($q) => $q->whereIn('slug', [RoleSlug::Teacher->value, RoleSlug::Coach->value, RoleSlug::Admin->value]))->get(),
            AnnouncementAudience::ClassRoom => $query->whereHas('classMemberships', fn ($q) => $q->where('class_id', $announcement->class_id)->where('status', 'active')->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->get(),
            AnnouncementAudience::Session => $query->whereHas('classMemberships', fn ($q) => $q->where('academic_year_id', $announcement->learningSession->academic_year_id)->where('status', 'active')->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))->get(),
            default => $query->where(function ($q) use ($scopeStudents): void {
                $q->whereDoesntHave('roles', fn ($roles) => $roles->where('slug', RoleSlug::Student->value))
                    ->orWhereHas('classMemberships', $scopeStudents);
            })->get(),
        };
    }

    public static function programMeta(?int $programBatchId): array
    {
        if (! $programBatchId) {
            return [];
        }

        $batch = ProgramBatch::query()->with(['program', 'institution'])->find($programBatchId);

        if (! $batch) {
            return ['program_batch_id' => $programBatchId];
        }

        return [
            'program_batch_id' => $batch->id,
            'program_name' => $batch->program?->name,
            'program_context' => collect([$batch->program?->name, $batch->institution?->name, $batch->period_label])->filter()->implode(' · '),
        ];
    }

    /** @param array<string, mixed> $data */
    private function resolveProgramBatchId(array $data, User $actor): ?int
    {
        if (! empty($data['class_id'])) {
            return SchoolClass::query()->whereKey($data['class_id'])->value('program_batch_id');
        }

        if (! empty($data['learning_session_id'])) {
            return LearningSession::query()->whereKey($data['learning_session_id'])->value('program_batch_id');
        }

        return app(ProgramContextService::class)->activeBatchId($actor);
    }
}
