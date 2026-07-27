<?php

namespace App\Actions\Learning;

use App\Enums\LearningSessionStatus;
use App\Models\LearningSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishLearningSession
{
    public function execute(LearningSession $learningSession, ?User $publisher): LearningSession
    {
        return DB::transaction(function () use ($learningSession, $publisher): LearningSession {
            $session = LearningSession::query()->lockForUpdate()->findOrFail($learningSession->id);

            if (empty($session->objectives)) {
                throw ValidationException::withMessages(['learning_session' => 'Pertemuan membutuhkan minimal satu tujuan pembelajaran.']);
            }

            if (! $session->materials()->exists()) {
                throw ValidationException::withMessages(['learning_session' => 'Tambahkan minimal satu materi sebelum dipublikasikan.']);
            }

            if ($session->status->isVisibleToStudents() && $session->published_at !== null) {
                return $session;
            }

            if ($session->status === LearningSessionStatus::Archived) {
                throw ValidationException::withMessages(['learning_session' => 'Pertemuan yang diarsipkan tidak dapat dipublikasikan.']);
            }

            $session->update([
                'status' => LearningSessionStatus::Published,
                'published_at' => now(),
                'published_by' => $publisher?->id,
                'updated_by' => $publisher?->id,
            ]);

            return $session->refresh();
        });
    }
}
