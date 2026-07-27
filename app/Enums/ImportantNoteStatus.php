<?php

namespace App\Enums;

enum ImportantNoteStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::InProgress => 'Ditangani',
            self::Resolved => 'Selesai',
            self::Verified => 'Terverifikasi',
        };
    }
}
