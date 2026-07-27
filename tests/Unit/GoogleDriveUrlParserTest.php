<?php

namespace Tests\Unit;

use App\Services\GoogleDriveUrlParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GoogleDriveUrlParserTest extends TestCase
{
    #[DataProvider('driveUrlProvider')]
    public function test_supported_drive_urls_are_parsed(string $url, string $fileId, string $previewUrl): void
    {
        $parsed = (new GoogleDriveUrlParser)->parse($url);

        $this->assertNotNull($parsed);
        $this->assertSame($fileId, $parsed->fileId);
        $this->assertSame($previewUrl, $parsed->previewUrl);
    }

    public static function driveUrlProvider(): array
    {
        $id = '1AbCdEfGhIjKlMnOpQrStUvWxYz';

        return [
            'drive file' => ["https://drive.google.com/file/d/{$id}/view", $id, "https://drive.google.com/file/d/{$id}/preview"],
            'document' => ["https://docs.google.com/document/d/{$id}/edit", $id, "https://docs.google.com/document/d/{$id}/preview"],
            'spreadsheet' => ["https://docs.google.com/spreadsheets/d/{$id}/edit", $id, "https://docs.google.com/spreadsheets/d/{$id}/preview"],
            'presentation' => ["https://docs.google.com/presentation/d/{$id}/edit", $id, "https://docs.google.com/presentation/d/{$id}/preview"],
            'open query' => ["https://drive.google.com/open?id={$id}", $id, "https://drive.google.com/file/d/{$id}/preview"],
            'folder' => ["https://drive.google.com/drive/folders/{$id}", $id, "https://drive.google.com/embeddedfolderview?id={$id}#list"],
        ];
    }

    public function test_non_google_or_missing_file_id_is_rejected(): void
    {
        $parser = new GoogleDriveUrlParser;

        $this->assertNull($parser->parse('https://example.com/file.pdf'));
        $this->assertNull($parser->parse('https://drive.google.com/drive/my-drive'));
        $this->assertNull($parser->parse('not-a-url'));
    }

    public function test_drive_url_can_be_converted_to_thumbnail_url(): void
    {
        $fileId = '1AbCdEfGhIjKlMnOpQrStUvWxYz';

        $this->assertSame(
            "https://drive.google.com/thumbnail?id={$fileId}&sz=w1200",
            (new GoogleDriveUrlParser)->thumbnailUrl("https://drive.google.com/file/d/{$fileId}/view")
        );
    }
}
