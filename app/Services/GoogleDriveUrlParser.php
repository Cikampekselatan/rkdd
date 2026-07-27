<?php

namespace App\Services;

use App\Data\ParsedDriveUrl;

class GoogleDriveUrlParser
{
    public function parse(string $url): ?ParsedDriveUrl
    {
        $parts = parse_url(trim($url));
        $host = mb_strtolower($parts['host'] ?? '');

        if (! in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
            return null;
        }

        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);
        $fileId = $query['id'] ?? null;

        if (! $fileId && preg_match('~/(?:file/d|document/d|spreadsheets/d|presentation/d|folders)/([a-zA-Z0-9_-]+)~', $path, $matches)) {
            $fileId = $matches[1];
        }

        if (! is_string($fileId) || ! preg_match('/^[a-zA-Z0-9_-]{10,}$/', $fileId)) {
            return null;
        }

        $previewUrl = match (true) {
            str_contains($path, '/document/d/') => "https://docs.google.com/document/d/{$fileId}/preview",
            str_contains($path, '/spreadsheets/d/') => "https://docs.google.com/spreadsheets/d/{$fileId}/preview",
            str_contains($path, '/presentation/d/') => "https://docs.google.com/presentation/d/{$fileId}/preview",
            str_contains($path, '/folders/') => "https://drive.google.com/embeddedfolderview?id={$fileId}#list",
            default => "https://drive.google.com/file/d/{$fileId}/preview",
        };

        return new ParsedDriveUrl($fileId, $previewUrl);
    }

    public function thumbnailUrl(string $url, int $width = 1200): ?string
    {
        $parsed = $this->parse($url);

        if ($parsed === null) {
            return null;
        }

        return "https://drive.google.com/thumbnail?id={$parsed->fileId}&sz=w{$width}";
    }
}
