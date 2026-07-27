<?php

namespace App\Services;

use App\Enums\RoleSlug;
use App\Models\ClassStudent;
use App\Models\ProgramBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProgramContextService
{
    public const SESSION_KEY = 'active_program_batch_id';

    /**
     * @return Collection<int, ProgramBatch>
     */
    public function availableBatches(User $user): Collection
    {
        if (! $user->isStaff()) {
            $batchIds = $user->classMemberships()
                ->where('status', 'active')
                ->whereNotNull('program_batch_id')
                ->pluck('program_batch_id')
                ->unique()
                ->values();

            if ($batchIds->isEmpty()) {
                return new Collection;
            }

            return ProgramBatch::query()
                ->with(['program', 'institution'])
                ->whereIn('id', $batchIds)
                ->where('is_active', true)
                ->whereHas('program', fn ($query) => $query->where('is_active', true))
                ->whereHas('institution', fn ($query) => $query->where('is_active', true))
                ->orderBy('name')
                ->get();
        }

        $query = ProgramBatch::query()
            ->with(['program', 'institution'])
            ->where('is_active', true)
            ->whereHas('program', fn ($query) => $query->where('is_active', true))
            ->whereHas('institution', fn ($query) => $query->where('is_active', true));

        if (! $user->hasRole(RoleSlug::SuperAdmin)) {
            $assignedBatchIds = $user->assignedProgramBatches()
                ->pluck('program_batches.id')
                ->unique()
                ->values();

            if ($assignedBatchIds->isNotEmpty()) {
                $query->whereIn('id', $assignedBatchIds);
            }
        }

        return $query->orderBy('name')->get();
    }

    public function activeBatch(User $user): ?ProgramBatch
    {
        $available = $this->availableBatches($user);

        if ($available->isEmpty()) {
            return null;
        }

        $sessionBatchId = session(self::SESSION_KEY);
        $active = $sessionBatchId ? $available->firstWhere('id', (int) $sessionBatchId) : null;

        if (! $active) {
            $active = $available->first(fn (ProgramBatch $batch) => $batch->program->slug === 'skuad')
                ?? $available->first();

            session([self::SESSION_KEY => $active->id]);
        }

        return $active;
    }

    public function activeBatchId(User $user): ?int
    {
        return $this->activeBatch($user)?->id;
    }

    public function studentActiveMembership(User $user): ?ClassStudent
    {
        if ($user->isStaff()) {
            return null;
        }

        $activeBatchId = $this->activeBatchId($user);

        return $user->classMemberships()
            ->with(['programBatch.program', 'programBatch.institution', 'schoolClass.academicYear'])
            ->where('status', 'active')
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->latest('joined_at')
            ->first();
    }

    public function participantLabel(User $user, bool $plural = false): string
    {
        $label = $this->activeBatch($user)?->participant_label ?: 'Peserta';

        return $plural ? $label : $label;
    }

    public function groupLabel(User $user): string
    {
        return match ($this->activeBatch($user)?->audience_type) {
            'school' => 'Kelompok/Angkatan',
            default => 'Kelompok/Angkatan',
        };
    }

    public function periodLabel(User $user): string
    {
        return match ($this->activeBatch($user)?->audience_type) {
            'school' => 'Periode/Tahun Ajaran',
            default => 'Periode Program',
        };
    }

    public function setActiveBatch(User $user, ProgramBatch $batch): bool
    {
        if (
            $user->isStaff()
            && ! $user->hasRole(RoleSlug::SuperAdmin)
            && ! $user->assignedProgramBatches()->whereKey($batch->id)->exists()
        ) {
            return false;
        }

        if (! $this->availableBatches($user)->contains('id', $batch->id)) {
            return false;
        }

        session([self::SESSION_KEY => $batch->id]);

        return true;
    }
}
