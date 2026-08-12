<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification;

class NotificationProgramScope
{
    public function __construct(private readonly ProgramContextService $programContext) {}

    public function activeBatchId(User $user): ?int
    {
        return $this->programContext->activeBatchId($user);
    }

    /**
     * @param  Builder<DatabaseNotification>|Relation<DatabaseNotification, User, mixed>  $query
     * @return Builder<DatabaseNotification>|Relation<DatabaseNotification, User, mixed>
     */
    public function apply(Builder|Relation $query, User $user): Builder|Relation
    {
        $activeBatchId = $this->activeBatchId($user);

        if (! $activeBatchId) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($activeBatchId): void {
            $query->where('data->program_batch_id', $activeBatchId)
                ->orWhereNull('data->program_batch_id');
        });
    }
}
