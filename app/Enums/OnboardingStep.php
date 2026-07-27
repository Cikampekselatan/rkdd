<?php

namespace App\Enums;

enum OnboardingStep: string
{
    case Identity = 'identity';
    case Guardian = 'guardian';
    case Access = 'access';
    case Interests = 'interests';
    case Agreements = 'agreements';

    public function number(): int
    {
        return match ($this) {
            self::Identity => 1,
            self::Guardian => 2,
            self::Access => 3,
            self::Interests => 4,
            self::Agreements => 5,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Identity => 'Identitas',
            self::Guardian => 'Orang Tua/Wali',
            self::Access => 'Perangkat & Internet',
            self::Interests => 'Minat & Kemampuan',
            self::Agreements => 'Persetujuan',
        };
    }

    public function next(): ?self
    {
        return self::cases()[$this->number()] ?? null;
    }

    public function previous(): ?self
    {
        return self::cases()[$this->number() - 2] ?? null;
    }
}
