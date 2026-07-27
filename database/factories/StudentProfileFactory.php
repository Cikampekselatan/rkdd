<?php

namespace Database\Factories;

use App\Enums\StudentMembershipStatus;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'student_number' => fake()->unique()->numerify('SKUAD-#####'),
            'nisn' => fake()->unique()->numerify('##########'),
            'nickname' => fake()->firstName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->dateTimeBetween('-16 years', '-11 years'),
            'grade_level' => fake()->numberBetween(7, 9),
            'school_class_name' => fn (array $attributes) => $attributes['grade_level'].fake()->randomElement(['A', 'B', 'C']),
            'class_id' => SchoolClass::factory(),
            'parent_name' => fake()->name(),
            'parent_phone' => '08'.fake()->numerify('##########'),
            'guardian_relationship' => 'Orang Tua',
            'address' => fake()->address(),
            'joined_at' => null,
            'membership_status' => StudentMembershipStatus::Onboarding,
        ];
    }
}
