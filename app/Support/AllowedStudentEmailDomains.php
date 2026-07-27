<?php

namespace App\Support;

class AllowedStudentEmailDomains
{
    /**
     * @return list<string>
     */
    public function all(): array
    {
        return collect(config('student-registration.allowed_email_domains', []))
            ->filter(fn (mixed $domain): bool => is_string($domain) && trim($domain) !== '')
            ->map(fn (string $domain): string => mb_strtolower(trim($domain)))
            ->unique()
            ->values()
            ->all();
    }

    public function allows(string $email): bool
    {
        $domain = $this->domainFrom($email);

        return $domain !== null && in_array($domain, $this->all(), true);
    }

    public function domainFrom(string $email): ?string
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return mb_strtolower((string) str($email)->afterLast('@'));
    }
}
