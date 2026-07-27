<?php

namespace App\Services;

use App\Models\ActivityDocumentation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivityDocumentationService
{
    private const MAX_BYTES = 512000;

    /** @param array<string, mixed> $data */
    public function save(?ActivityDocumentation $documentation, array $data, ?UploadedFile $photo, User $actor): ActivityDocumentation
    {
        return DB::transaction(function () use ($actor, $data, $documentation, $photo): ActivityDocumentation {
            unset($data['photo']);
            $photoData = $photo ? $this->photoData($photo, $actor) : [];
            $oldPath = $documentation?->photo_path;

            if (! $documentation) {
                $documentation = ActivityDocumentation::query()->create([...$data, ...$photoData, 'program_batch_id' => $data['program_batch_id'] ?? app(ProgramContextService::class)->activeBatchId($actor), 'created_by' => $actor->id]);
            } else {
                $documentation->update([...$data, ...$photoData, 'program_batch_id' => $documentation->program_batch_id ?? $data['program_batch_id'] ?? app(ProgramContextService::class)->activeBatchId($actor)]);
            }

            if ($photoData && $oldPath && $oldPath !== $documentation->photo_path) {
                Storage::disk('public')->delete($oldPath);
            }

            return $documentation->refresh();
        });
    }

    public function delete(ActivityDocumentation $documentation): void
    {
        DB::transaction(function () use ($documentation): void {
            if ($documentation->photo_path) {
                Storage::disk('public')->delete($documentation->photo_path);
            }

            $documentation->delete();
        });
    }

    /**
     * @return array{photo_path: string, photo_original_name: string}
     */
    private function photoData(UploadedFile $photo, User $actor): array
    {
        $image = @imagecreatefromstring($photo->getContent());

        if (! $image) {
            throw ValidationException::withMessages(['photo' => 'File foto dokumentasi tidak dapat dibaca sebagai gambar.']);
        }

        $image = $this->resize($image, 1200);
        $encoded = $this->encodeWithinLimit($image);
        imagedestroy($image);

        $path = 'activity-documentations/user-'.$actor->id.'-'.Str::random(12).'.jpg';
        Storage::disk('public')->put($path, $encoded);

        return ['photo_path' => $path, 'photo_original_name' => $photo->getClientOriginalName()];
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

        foreach ([900, 720, 600, 500, 420, 360] as $dimension) {
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
