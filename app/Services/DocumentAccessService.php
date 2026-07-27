<?php

namespace App\Services;

use App\Enums\DocumentAudience;
use App\Enums\DocumentCategory;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\DocumentResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DocumentAccessService
{
    public function canManage(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Teacher]);
    }

    public function canView(User $user, DocumentResource $resource): bool
    {
        if ($this->canManage($user)) {
            return $this->matchesActiveProgram($user, $resource->program_batch_id);
        }

        if (! $resource->is_active || $resource->published_at === null || $resource->published_at->isFuture()) {
            return false;
        }

        if (! $this->matchesActiveProgram($user, $resource->program_batch_id)) {
            return false;
        }

        if (! in_array($resource->audience->value, DocumentAudience::visibleValuesFor($user), true)) {
            return false;
        }

        if ($user->hasRole(RoleSlug::Student)) {
            $membership = app(ProgramContextService::class)->studentActiveMembership($user);

            return $user->status === UserStatus::Active
                && $resource->category->isStudentLibrary()
                && ($resource->academic_year_id === null
                    || $resource->academic_year_id === $membership?->academic_year_id
                    || $resource->academic_year_id === $user->studentProfile?->schoolClass?->academic_year_id);
        }

        return $user->isStaff();
    }

    /** @return Builder<DocumentResource> */
    public function queryFor(User $user): Builder
    {
        $query = DocumentResource::query();
        $programBatchId = app(ProgramContextService::class)->activeBatchId($user);

        if ($this->canManage($user)) {
            return $query->when($programBatchId, fn (Builder $query, int $batchId) => $query->where('program_batch_id', $batchId));
        }

        $query->published()
            ->whereIn('audience', DocumentAudience::visibleValuesFor($user))
            ->when($programBatchId, fn (Builder $query, int $batchId) => $query->where(fn (Builder $query) => $query->whereNull('program_batch_id')->orWhere('program_batch_id', $batchId)));

        if ($user->hasRole(RoleSlug::Student)) {
            $membership = app(ProgramContextService::class)->studentActiveMembership($user);
            $academicYearId = $membership?->academic_year_id
                ?? $user->studentProfile?->schoolClass?->academic_year_id;
            $query
                ->whereIn('category', DocumentCategory::studentLibraryValues())
                ->where(fn (Builder $query) => $query
                    ->whereNull('academic_year_id')
                    ->orWhere('academic_year_id', $academicYearId));
        }

        return $query;
    }

    public function canManageResource(User $user, DocumentResource $resource): bool
    {
        return $this->canManage($user) && $this->matchesActiveProgram($user, $resource->program_batch_id);
    }

    private function matchesActiveProgram(User $user, ?int $programBatchId): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);

        return $programBatchId === null || $activeBatchId === null || $programBatchId === $activeBatchId;
    }
}
