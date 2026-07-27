<?php

namespace App\Exceptions;

use RuntimeException;

class DisallowedGoogleEmailDomain extends RuntimeException
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $domain,
    ) {
        parent::__construct('Domain email Google tidak diizinkan.');
    }
}
