<?php

namespace App\Data;

readonly class ParsedDriveUrl
{
    public function __construct(
        public string $fileId,
        public string $previewUrl,
    ) {}
}
