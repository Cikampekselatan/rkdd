<?php

namespace App\Enums;

enum PortfolioWorkType: string
{
    case Poster = 'poster';
    case Infographic = 'infographic';
    case Photo = 'photo';
    case Video = 'video';
    case Presentation = 'presentation';
    case Report = 'report';
    case Coding = 'coding';
    case Website = 'website';
    case Catalog = 'catalog';
    case FinalProject = 'final_project';
    case Certificate = 'certificate';

    public function label(): string
    {
        return match ($this) {
            self::Poster => 'Poster',self::Infographic => 'Infografis',self::Photo => 'Foto',self::Video => 'Video',self::Presentation => 'Presentasi',self::Report => 'Laporan',self::Coding => 'Coding',self::Website => 'Website',self::Catalog => 'Katalog',self::FinalProject => 'Proyek akhir',self::Certificate => 'Sertifikat'
        };
    }
}
