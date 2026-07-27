<?php

namespace Database\Factories;

use App\Enums\DiscussionStatus;
use App\Models\DiscussionTopic;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiscussionTopic> */
class DiscussionTopicFactory extends Factory
{
    public function definition(): array
    {
        return ['class_id' => SchoolClass::factory(), 'academic_year_id' => fn ($a) => SchoolClass::find($a['class_id'])?->academic_year_id, 'created_by' => User::factory(), 'title' => fake()->sentence(5), 'body' => fake()->paragraph(), 'status' => DiscussionStatus::Open, 'is_pinned' => false, 'is_hidden' => false];
    }
}
