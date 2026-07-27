<?php

namespace App\Enums;

enum LearningMaterialType: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Document = 'document';
    case Link = 'link';
    case Audio = 'audio';
    case Presentation = 'presentation';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Teks',
            self::Image => 'Gambar',
            self::Video => 'Video',
            self::Document => 'Dokumen',
            self::Link => 'Tautan',
            self::Audio => 'Audio',
            self::Presentation => 'Presentasi',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Text => 'bi-file-text',
            self::Image => 'bi-image',
            self::Video => 'bi-play-btn',
            self::Document => 'bi-file-earmark-arrow-down',
            self::Link => 'bi-link-45deg',
            self::Audio => 'bi-headphones',
            self::Presentation => 'bi-easel2',
        };
    }
}
