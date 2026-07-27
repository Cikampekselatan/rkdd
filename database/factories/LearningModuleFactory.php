<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\LearningModule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<LearningModule> */
class LearningModuleFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->numberBetween(1, 15);
        $title = "Modul Kreatif {$number}";

        return [
            'academic_year_id' => AcademicYear::factory(),
            'module_number' => $number,
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
            'sort_order' => $number,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
