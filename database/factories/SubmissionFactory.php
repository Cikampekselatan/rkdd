<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Submission> */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return ['assignment_id' => Assignment::factory(), 'user_id' => User::factory(), 'status' => SubmissionStatus::Draft, 'current_version_number' => 1, 'revision_count' => 0];
    }
}
