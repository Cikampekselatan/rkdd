<?php

namespace App\Actions\RegistrationCodes;

use App\Exceptions\RegistrationCodeRejected;
use App\Models\RegistrationCode;
use App\Services\RegistrationCodeHasher;

class ValidateRegistrationCode
{
    public function __construct(private readonly RegistrationCodeHasher $hasher) {}

    public function execute(string $plainText): RegistrationCode
    {
        $registrationCode = RegistrationCode::query()
            ->where('code_hash', $this->hasher->hash($plainText))
            ->first();

        if ($registrationCode === null) {
            throw new RegistrationCodeRejected('not_found', 'Kode pendaftaran tidak ditemukan.');
        }

        $this->ensureAvailable($registrationCode);

        return $registrationCode;
    }

    public function ensureAvailable(RegistrationCode $registrationCode): void
    {
        if (! $registrationCode->is_active) {
            throw new RegistrationCodeRejected('inactive', 'Kode pendaftaran sedang tidak aktif.');
        }

        if ($registrationCode->starts_at?->isFuture()) {
            throw new RegistrationCodeRejected('not_started', 'Kode pendaftaran belum dapat digunakan.');
        }

        if ($registrationCode->expires_at?->isPast()) {
            throw new RegistrationCodeRejected('expired', 'Kode pendaftaran sudah kedaluwarsa.');
        }

        if ($registrationCode->hasReachedUsageLimit()) {
            throw new RegistrationCodeRejected('usage_limit', 'Batas penggunaan kode pendaftaran sudah tercapai.');
        }
    }
}
