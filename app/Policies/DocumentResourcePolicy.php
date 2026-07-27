<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\DocumentResource;
use App\Models\User;
use App\Services\DocumentAccessService;

class DocumentResourcePolicy
{
    public function __construct(private readonly DocumentAccessService $access) {}

    public function viewAny(User $user): bool
    {
        return $user->isStaff()
            || ($user->hasRole(RoleSlug::Student) && $user->status === UserStatus::Active);
    }

    public function view(User $user, DocumentResource $documentResource): bool
    {
        return $this->access->canView($user, $documentResource);
    }

    public function create(User $user): bool
    {
        return $this->access->canManage($user);
    }

    public function update(User $user, DocumentResource $documentResource): bool
    {
        return $this->access->canManageResource($user, $documentResource);
    }

    public function delete(User $user, DocumentResource $documentResource): bool
    {
        return $this->access->canManageResource($user, $documentResource);
    }

    public function publish(User $user, DocumentResource $documentResource): bool
    {
        return $this->access->canManageResource($user, $documentResource);
    }
}
