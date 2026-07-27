<?php

namespace App\Enums;

enum RemedialStatus: string
{
    case None = 'none';
    case Assigned = 'assigned';
    case Submitted = 'submitted';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tidak ada',self::Assigned => 'Perlu remedial',self::Submitted => 'Remedial dikirim',self::Completed => 'Remedial selesai'
        };
    }
}
