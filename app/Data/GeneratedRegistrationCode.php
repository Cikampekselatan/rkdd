<?php

namespace App\Data;

use App\Models\RegistrationCode;

readonly class GeneratedRegistrationCode
{
    public function __construct(
        public RegistrationCode $registrationCode,
        public string $plainText,
    ) {}
}
