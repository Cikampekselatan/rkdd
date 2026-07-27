<?php

namespace App\Enums;

enum PortfolioApprovalStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Tidak diperlukan',self::Pending => 'Menunggu persetujuan',self::Approved => 'Disetujui',self::Rejected => 'Ditolak'
        };
    }
}
