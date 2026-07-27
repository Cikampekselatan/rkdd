<?php

namespace Database\Factories;

use App\Enums\LearningMaterialType;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LearningMaterial> */
class LearningMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'learning_session_id' => LearningSession::factory(),
            'type' => LearningMaterialType::Text,
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(2, true),
            'url' => null,
            'sort_order' => 1,
            'is_required' => true,
        ];
    }
}
