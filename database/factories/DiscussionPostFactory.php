<?php

namespace Database\Factories;

use App\Models\DiscussionPost;
use App\Models\DiscussionTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiscussionPost> */
class DiscussionPostFactory extends Factory
{
    public function definition(): array
    {
        return ['topic_id' => DiscussionTopic::factory(), 'user_id' => User::factory(), 'parent_id' => null, 'body' => fake()->paragraph(), 'is_hidden' => false];
    }
}
