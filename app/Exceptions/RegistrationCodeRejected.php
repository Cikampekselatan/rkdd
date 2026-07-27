<?php

namespace App\Exceptions;

use RuntimeException;

class RegistrationCodeRejected extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
