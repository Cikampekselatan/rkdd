<?php

namespace App\Services;

use App\Models\LearningSession;
use App\Models\StudentLearningProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentLearningProgressService
{
    public function recordOpened(User $student, LearningSession $session): StudentLearningProgress
    {
        return DB::transaction(function () use ($session, $student): StudentLearningProgress {
            $progress = StudentLearningProgress::query()->firstOrCreate(
                ['user_id' => $student->id, 'learning_session_id' => $session->id],
                ['opened_at' => now()],
            );
            $progress->forceFill([
                'opened_at' => $progress->opened_at ?? now(),
                'last_accessed_at' => now(),
            ])->save();

            return $this->recalculate($progress, $session);
        });
    }

    public function completeComponent(User $student, LearningSession $session, string $component): StudentLearningProgress
    {
        return DB::transaction(function () use ($component, $session, $student): StudentLearningProgress {
            $progress = $this->recordOpened($student, $session);
            $field = match ($component) {
                'materials' => 'materials_completed_at',
                'exercise' => 'exercise_completed_at',
                'reflection' => 'reflection_completed_at',
                default => throw ValidationException::withMessages(['component' => 'Komponen progress tidak valid.']),
            };

            if ($component === 'materials' && ! $session->materials()->exists()) {
                throw ValidationException::withMessages(['component' => 'Pertemuan ini belum memiliki materi.']);
            }

            if ($component === 'exercise' && blank($session->practice_instructions)) {
                throw ValidationException::withMessages(['component' => 'Pertemuan ini belum memiliki latihan.']);
            }

            if ($component === 'reflection' && blank($session->reflection_prompt)) {
                throw ValidationException::withMessages(['component' => 'Pertemuan ini belum memiliki refleksi.']);
            }

            $progress->forceFill([$field => $progress->{$field} ?? now(), 'last_accessed_at' => now()])->save();

            return $this->recalculate($progress, $session);
        });
    }

    private function recalculate(StudentLearningProgress $progress, LearningSession $session): StudentLearningProgress
    {
        $components = [
            $progress->opened_at !== null,
            $session->materials()->exists() ? $progress->materials_completed_at !== null : null,
            filled($session->practice_instructions) ? $progress->exercise_completed_at !== null : null,
            filled($session->reflection_prompt) ? $progress->reflection_completed_at !== null : null,
        ];
        $applicable = collect($components)->filter(fn (?bool $value): bool => $value !== null);
        $completed = $applicable->filter()->count();
        $percent = $applicable->isEmpty() ? 0 : (int) round(($completed / $applicable->count()) * 100);

        $progress->forceFill([
            'progress_percent' => $percent,
            'completed_at' => $percent === 100 ? ($progress->completed_at ?? now()) : null,
        ])->save();

        return $progress->refresh();
    }
}
