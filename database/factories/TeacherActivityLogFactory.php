<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Enums\TeacherActivityStatus;
use App\Models\AcademicYear;
use App\Models\TeacherActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeacherActivityLog> */
class TeacherActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return ['academic_year_id' => AcademicYear::factory(), 'teacher_id' => User::factory()->withRole(RoleSlug::Teacher), 'log_number' => fake()->unique()->numberBetween(1, 9999), 'activity_date' => fake()->date(), 'material' => fake()->sentence(), 'activities' => fake()->paragraph(), 'assignment' => fake()->sentence(), 'signature_path' => null, 'signature_original_name' => null, 'status' => TeacherActivityStatus::Draft, 'submitted_at' => null, 'verified_by' => null, 'verified_at' => null, 'rejection_note' => null];
    }
}
