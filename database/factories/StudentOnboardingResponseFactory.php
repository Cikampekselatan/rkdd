<?php

namespace Database\Factories;

use App\Models\StudentOnboardingResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentOnboardingResponse>
 */
class StudentOnboardingResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'registration_code_id' => null,
            'device_access' => ['android'],
            'internet_access' => 'stable',
            'willing_to_share_device' => true,
            'digital_apps' => ['Canva', 'Google Docs'],
            'interests' => ['design', 'photography'],
            'initial_skills' => ['presentation'],
            'experience' => 'Pernah membuat poster kelas.',
            'expectation' => 'Ingin lebih kreatif dan percaya diri.',
            'learning_targets' => 'Mampu membuat portofolio digital.',
            'current_step' => 5,
            'completed_at' => null,
        ];
    }
}
