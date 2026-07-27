<?php

namespace App\Services;

class RegistrationCodeHasher
{
    public function hash(string $plainText): string
    {
        return hash_hmac('sha256', $this->normalize($plainText), $this->key());
    }

    public function normalize(string $plainText): string
    {
        return mb_strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', trim($plainText)));
    }

    public function hint(string $plainText): string
    {
        return mb_substr($this->normalize($plainText), -8);
    }

    private function key(): string
    {
        $key = (string) config('registration-codes.hash_key');

        if ($key === '') {
            throw new \RuntimeException('Registration code hash key is not configured.');
        }

        return $key;
    }
}
