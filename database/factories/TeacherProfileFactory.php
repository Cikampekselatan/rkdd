<?php

namespace Database\Factories;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherProfile>
 */
class TeacherProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_number' => fake()->unique()->numerify('PEG-#####'),
            'phone' => '08'.fake()->numerify('##########'),
            'specialization' => fake()->randomElement(['Desain', 'Fotografi', 'Coding', 'Pembinaan']),
            'bio' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
