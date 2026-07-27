<?php

namespace App\Enums;

enum ShowcaseMediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Foto/Gambar',
            self::Video => 'Video',
            self::Audio => 'Audio',
            self::Document => 'Dokumen',
            self::Link => 'Tautan karya',
        };
    }

    public static function detectFromUrl(string $url): self
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/', $path)) {
            return self::Image;
        }

        if (preg_match('/\.(mp4|webm|mov)$/', $path) || str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            return self::Video;
        }

        if (preg_match('/\.(mp3|wav|ogg)$/', $path)) {
            return self::Audio;
        }

        if (preg_match('/\.(pdf|doc|docx|ppt|pptx)$/', $path) || str_contains($host, 'drive.google.com')) {
            return self::Document;
        }

        return self::Link;
    }
}
