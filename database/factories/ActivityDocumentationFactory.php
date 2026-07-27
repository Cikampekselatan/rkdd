<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\ActivityDocumentation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ActivityDocumentation> */
class ActivityDocumentationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'created_by' => User::factory()->withRole(RoleSlug::Teacher),
            'activity_date' => today(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'photo_path' => null,
            'photo_original_name' => null,
            'resource_url' => 'https://example.com/dokumentasi',
            'video_url' => null,
        ];
    }
}
