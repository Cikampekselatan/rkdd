<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfilePhotoService
{
    private const MAX_BYTES = 512000;

    public function store(User $user, UploadedFile $photo): string
    {
        if (! extension_loaded('gd')) {
            throw ValidationException::withMessages(['photo' => 'Server belum mengaktifkan ekstensi PHP GD untuk memproses foto.']);
        }

        $image = @imagecreatefromstring($photo->getContent());

        if (! $image) {
            throw ValidationException::withMessages(['photo' => 'File foto tidak dapat dibaca sebagai gambar.']);
        }

        $image = $this->resize($image, 900);
        $encoded = $this->encodeWithinLimit($image);
        imagedestroy($image);

        $path = 'profile-photos/user-'.$user->id.'-'.Str::random(12).'.jpg';

        if (! Storage::disk('public')->put($path, $encoded)) {
            throw ValidationException::withMessages(['photo' => 'Foto gagal disimpan. Periksa permission storage aplikasi.']);
        }

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->forceFill(['profile_photo_path' => $path])->save();

        return $path;
    }

    public function delete(User $user): void
    {
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->forceFill(['profile_photo_path' => null])->save();
        }
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
        $current = $image;

        foreach ([85, 75, 65, 55, 45, 35] as $quality) {
            $encoded = $this->jpeg($current, $quality);

            if (strlen($encoded) <= self::MAX_BYTES) {
                if ($current !== $image) {
                    imagedestroy($current);
                }

                return $encoded;
            }
        }

        foreach ([720, 600, 500, 420, 360, 320] as $dimension) {
            $resized = $this->resize($image, $dimension);
            $encoded = $this->jpeg($resized, 35);
            imagedestroy($resized);

            if (strlen($encoded) <= self::MAX_BYTES) {
                return $encoded;
            }
        }

        return $this->jpeg($this->resize($image, 320), 30);
    }

    private function jpeg(\GdImage $image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }
}
