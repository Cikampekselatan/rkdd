<?php

namespace App\Data;

use App\Models\User;

readonly class GoogleAuthenticationResult
{
    public function __construct(
        public User $user,
        public bool $created,
    ) {}
}
