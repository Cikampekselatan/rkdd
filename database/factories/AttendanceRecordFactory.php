<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceRecord> */
class AttendanceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attendance_session_id' => AttendanceSession::factory(),
            'user_id' => User::factory(),
            'status' => AttendanceStatus::Present,
            'notes' => null,
            'recorded_by' => null,
            'recorded_at' => now(),
        ];
    }
}
