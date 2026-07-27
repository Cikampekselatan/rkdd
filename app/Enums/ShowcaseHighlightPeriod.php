<?php

namespace App\Enums;

enum ShowcaseHighlightPeriod: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Minggu ini',
            self::Monthly => 'Bulan ini',
        };
    }
}
