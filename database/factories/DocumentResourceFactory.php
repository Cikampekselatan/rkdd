<?php

namespace Database\Factories;

use App\Enums\DocumentAudience;
use App\Enums\DocumentCategory;
use App\Models\AcademicYear;
use App\Models\DocumentResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DocumentResource> */
class DocumentResourceFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $fileId = Str::random(28);

        return [
            'academic_year_id' => AcademicYear::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'category' => DocumentCategory::Guide,
            'description' => fake()->sentence(),
            'drive_url' => "https://drive.google.com/file/d/{$fileId}/view",
            'drive_file_id' => $fileId,
            'preview_url' => "https://drive.google.com/file/d/{$fileId}/preview",
            'audience' => DocumentAudience::StaffOnly,
            'semester' => null,
            'sort_order' => 0,
            'is_pinned' => false,
            'is_active' => false,
            'published_at' => null,
            'published_by' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function published(DocumentAudience $audience = DocumentAudience::Students): static
    {
        return $this->state(fn (): array => [
            'audience' => $audience,
            'is_active' => true,
            'published_at' => now(),
        ]);
    }
}
