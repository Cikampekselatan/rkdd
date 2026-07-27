<?php

namespace App\Enums;

enum AssignmentType: string
{
    case Text = 'text';
    case Document = 'document';
    case Image = 'image';
    case VideoLink = 'video_link';
    case ExternalLink = 'external_link';
    case Mixed = 'mixed';
    case Reflection = 'reflection';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Teks', self::Document => 'Dokumen', self::Image => 'Gambar',
            self::VideoLink => 'Tautan video', self::ExternalLink => 'Tautan karya',
            self::Mixed => 'Campuran', self::Reflection => 'Refleksi',
        };
    }

    public function acceptsFiles(): bool
    {
        return in_array($this, [self::Document, self::Image, self::Mixed], true);
    }

    public function acceptsText(): bool
    {
        return in_array($this, [self::Text, self::Mixed, self::Reflection], true);
    }
}
