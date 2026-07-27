<?php

namespace Database\Factories;

use App\Enums\AttendanceSessionStatus;
use App\Models\AttendanceSession;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceSession> */
class AttendanceSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'learning_session_id' => LearningSession::factory(),
            'academic_year_id' => fn (array $attributes) => LearningSession::query()->find($attributes['learning_session_id'])?->academic_year_id,
            'class_id' => fn (array $attributes) => SchoolClass::factory()->create(['academic_year_id' => $attributes['academic_year_id']])->id,
            'attendance_date' => today(),
            'status' => AttendanceSessionStatus::Open,
            'notes' => null,
            'opened_by' => null,
            'opened_at' => now(),
            'closed_by' => null,
            'closed_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => AttendanceSessionStatus::Closed, 'closed_at' => now()]);
    }
}
