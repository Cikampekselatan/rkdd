<?php

namespace App\Services;

use App\Models\Program;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProgramAssetService
{
    private const MAX_BYTES = 512000;

    public function storeLogo(Program $program, UploadedFile $logo): string
    {
        return $this->storeImage($program, $logo, 'logo_path', 640, 'program-logos');
    }

    public function storeBanner(Program $program, UploadedFile $banner): string
    {
        return $this->storeImage($program, $banner, 'banner_path', 1600, 'program-banners');
    }

    public function deleteAssets(Program $program): void
    {
        foreach (['logo_path', 'banner_path'] as $column) {
            if ($program->{$column}) {
                Storage::disk('public')->delete($program->{$column});
            }
        }
    }

    private function storeImage(Program $program, UploadedFile $file, string $column, int $maxDimension, string $directory): string
    {
        $image = @imagecreatefromstring($file->getContent());

        if (! $image) {
            throw ValidationException::withMessages([$column === 'logo_path' ? 'logo' : 'banner' => 'File gambar tidak dapat dibaca.']);
        }

        $image = $this->resize($image, $maxDimension);
        $encoded = $this->encodeWithinLimit($image);
        imagedestroy($image);

        $path = $directory.'/program-'.$program->id.'-'.Str::random(12).'.jpg';
        Storage::disk('public')->put($path, $encoded);

        if ($program->{$column}) {
            Storage::disk('public')->delete($program->{$column});
        }

        $program->forceFill([$column => $path])->save();

        return $path;
    }

    private function resize(\GdImage $source, int $maxDimension): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        if ($width <= $maxDimension && $height <= $maxDimension) {
            return $this->copyToTrueColor($source, $width, $height);
        }

        $ratio = min($maxDimension / $width, $maxDimension / $height);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $target;
    }

    private function copyToTrueColor(\GdImage $source, int $width, int $height): \GdImage
    {
        $target = imagecreatetruecolor($width, $height);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopy($target, $source, 0, 0, 0, 0, $width, $height);

        return $target;
    }

    private function encodeWithinLimit(\GdImage $image): string
    {
        foreach ([85, 75, 65, 55, 45, 35] as $quality) {
            $encoded = $this->jpeg($image, $quality);

            if (strlen($encoded) <= self::MAX_BYTES) {
                return $encoded;
            }
        }

        foreach ([1200, 960, 720, 600, 480, 360] as $dimension) {
            $resized = $this->resize($image, $dimension);
            $encoded = $this->jpeg($resized, 35);
            imagedestroy($resized);

            if (strlen($encoded) <= self::MAX_BYTES) {
                return $encoded;
            }
        }

        $resized = $this->resize($image, 320);
        $encoded = $this->jpeg($resized, 30);
        imagedestroy($resized);

        return $encoded;
    }

    private function jpeg(\GdImage $image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }
}
