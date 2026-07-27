<?php

namespace Database\Factories;

use App\Enums\RemedialStatus;
use App\Models\Grade;
use App\Models\Rubric;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Grade> */ class GradeFactory extends Factory
{
    public function definition(): array
    {
        return ['submission_id' => Submission::factory(), 'rubric_id' => Rubric::factory(), 'total_score' => 75, 'achievement_level' => 3, 'feedback' => fake()->sentence(), 'private_note' => null, 'is_published' => false, 'remedial_status' => RemedialStatus::None];
    }
}
