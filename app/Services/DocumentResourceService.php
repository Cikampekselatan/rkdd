<?php

namespace App\Services;

use App\Data\ParsedDriveUrl;
use App\Models\DocumentResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentResourceService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, ParsedDriveUrl $drive, User $actor): DocumentResource
    {
        return DB::transaction(function () use ($actor, $data, $drive): DocumentResource {
            $publishNow = (bool) ($data['publish_now'] ?? false);
            unset($data['publish_now']);
            $resource = DocumentResource::query()->create([
                ...$data,
                'program_batch_id' => $data['program_batch_id'] ?? app(ProgramContextService::class)->activeBatchId($actor),
                'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
                'drive_file_id' => $drive->fileId,
                'preview_url' => $drive->previewUrl,
                'is_active' => $publishNow,
                'published_at' => $publishNow ? now() : null,
                'published_by' => $publishNow ? $actor->id : null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $this->log($resource, $actor, 'created', ['published' => $publishNow]);

            return $resource;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(DocumentResource $resource, array $data, ParsedDriveUrl $drive, User $actor): DocumentResource
    {
        return DB::transaction(function () use ($actor, $data, $drive, $resource): DocumentResource {
            $publishNow = (bool) ($data['publish_now'] ?? false);
            unset($data['publish_now']);
            $resource->fill([
                ...$data,
                'program_batch_id' => $resource->program_batch_id ?? $data['program_batch_id'] ?? app(ProgramContextService::class)->activeBatchId($actor),
                'slug' => Str::slug($data['title']).'-'.$resource->id,
                'drive_file_id' => $drive->fileId,
                'preview_url' => $drive->previewUrl,
                'updated_by' => $actor->id,
            ]);
            $changedFields = array_keys($resource->getDirty());
            $resource->save();
            $this->log($resource, $actor, 'updated', ['fields' => $changedFields]);

            if ($publishNow && ! $resource->is_active) {
                return $this->publish($resource, $actor);
            }

            return $resource->refresh();
        });
    }

    public function publish(DocumentResource $resource, User $actor): DocumentResource
    {
        return DB::transaction(function () use ($actor, $resource): DocumentResource {
            $resource->update([
                'is_active' => true,
                'published_at' => now(),
                'published_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $this->log($resource, $actor, 'published');

            return $resource->refresh();
        });
    }

    public function archive(DocumentResource $resource, User $actor): DocumentResource
    {
        return DB::transaction(function () use ($actor, $resource): DocumentResource {
            $resource->update(['is_active' => false, 'updated_by' => $actor->id]);
            $this->log($resource, $actor, 'archived');

            return $resource->refresh();
        });
    }

    public function togglePin(DocumentResource $resource, User $actor): DocumentResource
    {
        return DB::transaction(function () use ($actor, $resource): DocumentResource {
            $resource->update(['is_pinned' => ! $resource->is_pinned, 'updated_by' => $actor->id]);
            $this->log($resource, $actor, $resource->is_pinned ? 'pinned' : 'unpinned');

            return $resource->refresh();
        });
    }

    public function delete(DocumentResource $resource, User $actor): void
    {
        DB::transaction(function () use ($actor, $resource): void {
            $this->log($resource, $actor, 'deleted');
            $resource->delete();
        });
    }

    /** @param array<string, mixed>|null $context */
    private function log(DocumentResource $resource, User $actor, string $event, ?array $context = null): void
    {
        $resource->logs()->create(['user_id' => $actor->id, 'event' => $event, 'context' => $context]);
    }
}
