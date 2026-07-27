<?php

namespace Tests\Feature;

use App\Enums\LearningSessionStatus;
use App\Models\LearningModule;
use App\Models\LearningSession;
use Database\Seeders\LearningCurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningCurriculumSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_curriculum_seeder_is_idempotent_and_creates_fifteen_modules_and_thirty_sessions(): void
    {
        $this->seed(LearningCurriculumSeeder::class);
        $this->seed(LearningCurriculumSeeder::class);

        $this->assertSame(15, LearningModule::query()->count());
        $this->assertSame(30, LearningSession::query()->count());
        $this->assertSame(15, LearningSession::query()->where('semester', 1)->count());
        $this->assertSame(15, LearningSession::query()->where('semester', 2)->count());
        $this->assertSame(30, LearningSession::query()->where('duration_minutes', 90)->count());
        $this->assertSame(30, LearningSession::query()->where('status', LearningSessionStatus::Draft->value)->count());
        $this->assertSame(range(1, 30), LearningSession::query()->orderBy('session_number')->pluck('session_number')->all());
        $this->assertDatabaseHas('learning_sessions', ['session_number' => 1, 'title' => 'Orientasi SKUAD dan Etika Digital']);
        $this->assertDatabaseHas('learning_sessions', ['session_number' => 30, 'title' => 'Pameran Karya dan Refleksi']);
    }

    public function test_seeder_does_not_restore_a_session_intentionally_removed_by_teacher(): void
    {
        $this->seed(LearningCurriculumSeeder::class);
        $session = LearningSession::query()->where('session_number', 1)->firstOrFail();
        $session->delete();

        $this->seed(LearningCurriculumSeeder::class);

        $this->assertSame(29, LearningSession::query()->count());
        $this->assertSame(30, LearningSession::withTrashed()->count());
        $this->assertSoftDeleted($session);
    }
}
