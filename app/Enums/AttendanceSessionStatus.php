<?php

namespace App\Enums;

enum AttendanceSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return $this === self::Open ? 'Terbuka' : 'Ditutup';
    }
}
