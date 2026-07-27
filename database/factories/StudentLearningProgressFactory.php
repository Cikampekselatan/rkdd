<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\LearningSession;
use App\Models\StudentLearningProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudentLearningProgress> */
class StudentLearningProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->withRole(RoleSlug::Student),
            'learning_session_id' => LearningSession::factory(),
            'progress_percent' => 25,
            'opened_at' => now(),
            'materials_completed_at' => null,
            'exercise_completed_at' => null,
            'assignment_completed_at' => null,
            'reflection_completed_at' => null,
            'completed_at' => null,
            'last_accessed_at' => now(),
        ];
    }
}
