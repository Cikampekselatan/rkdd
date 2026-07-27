<?php

namespace App\Enums;

enum AnnouncementPriority: string
{
    case Normal = 'normal';
    case Important = 'important';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal', self::Important => 'Penting', self::Urgent => 'Mendesak'
        };
    }
}
