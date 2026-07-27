<?php

namespace App\Console\Commands;

use App\Actions\Learning\PublishLearningSession;
use App\Enums\LearningSessionStatus;
use App\Models\LearningSession;
use Illuminate\Console\Command;

class PublishScheduledLearningSessions extends Command
{
    protected $signature = 'learning:publish-scheduled';

    protected $description = 'Publish due learning sessions that already have learning materials';

    public function handle(PublishLearningSession $publish): int
    {
        $publishedCount = 0;

        LearningSession::query()
            ->with(['creator', 'updater'])
            ->where('status', LearningSessionStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereHas('materials')
            ->orderBy('id')
            ->eachById(function (LearningSession $session) use ($publish, &$publishedCount): void {
                $publish->execute($session, $session->updater ?? $session->creator);
                $publishedCount++;
            });

        $this->info("Published {$publishedCount} scheduled learning session(s).");

        return self::SUCCESS;
    }
}
