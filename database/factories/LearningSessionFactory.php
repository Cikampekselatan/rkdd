<?php

namespace Database\Factories;

use App\Enums\LearningSessionStatus;
use App\Models\LearningModule;
use App\Models\LearningSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<LearningSession> */
class LearningSessionFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->numberBetween(1, 30);

        return [
            'learning_module_id' => LearningModule::factory(),
            'academic_year_id' => fn (array $attributes) => LearningModule::query()->find($attributes['learning_module_id'])?->academic_year_id,
            'session_number' => $number,
            'semester' => $number <= 15 ? 1 : 2,
            'title' => fn (array $attributes): string => "Pertemuan Kreatif {$attributes['session_number']}",
            'slug' => fn (array $attributes): string => $attributes['session_number'].'-'.Str::slug($attributes['title']),
            'duration_minutes' => 90,
            'objectives' => ['Memahami konsep utama.', 'Menerapkan konsep dalam latihan.'],
            'introduction' => fake()->paragraph(),
            'summary' => fake()->paragraph(),
            'practice_instructions' => fake()->sentence(),
            'reflection_prompt' => fake()->sentence(),
            'status' => LearningSessionStatus::Draft,
            'scheduled_at' => null,
            'published_at' => null,
            'published_by' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => LearningSessionStatus::Published,
            'published_at' => now(),
        ]);
    }
}
