<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Rubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rubric> */ class RubricFactory extends Factory
{
    public function definition(): array
    {
        return ['academic_year_id' => AcademicYear::factory(), 'name' => 'Rubrik '.fake()->words(3, true), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
