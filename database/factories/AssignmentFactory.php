<?php

namespace Database\Factories;

use App\Enums\AssignmentType;
use App\Models\Assignment;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assignment> */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return ['learning_session_id' => LearningSession::factory(), 'academic_year_id' => fn ($a) => LearningSession::find($a['learning_session_id'])?->academic_year_id, 'class_id' => fn ($a) => SchoolClass::factory()->create(['academic_year_id' => $a['academic_year_id']])->id, 'title' => fake()->sentence(4), 'instructions' => fake()->paragraph(), 'type' => AssignmentType::Text, 'available_from' => now()->subDay(), 'due_at' => now()->addWeek(), 'allow_late' => true, 'max_files' => 3, 'max_file_size_kb' => 5120, 'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'], 'max_revisions' => 1, 'is_published' => true, 'created_by' => null, 'updated_by' => null];
    }
}
