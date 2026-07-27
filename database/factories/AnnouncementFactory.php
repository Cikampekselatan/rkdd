<?php

namespace Database\Factories;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Announcement> */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return ['created_by' => User::factory(), 'title' => fake()->sentence(5), 'body' => fake()->paragraphs(2, true), 'audience' => AnnouncementAudience::All, 'priority' => AnnouncementPriority::Normal, 'published_at' => now(), 'expires_at' => null, 'is_pinned' => false, 'is_published' => true];
    }
}
