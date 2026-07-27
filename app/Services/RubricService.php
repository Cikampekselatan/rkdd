<?php

namespace App\Services;

use App\Models\Rubric;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RubricService
{
    private const LABELS = [1 => 'Perlu Pendampingan', 2 => 'Berkembang', 3 => 'Terampil', 4 => 'Kreator Mandiri'];

    public function save(?Rubric $rubric, array $data, User $actor): Rubric
    {
        return DB::transaction(function () use ($rubric, $data, $actor) {
            $criteria = $data['criteria'];
            unset($data['criteria']);
            if ($rubric && $rubric->criteria()->whereHas('levels', fn ($q) => $q->whereHas('submissionScores'))->exists()) {
                throw ValidationException::withMessages(['criteria' => 'Rubrik yang sudah dipakai menilai tidak dapat diubah strukturnya.']);
            }

            $data['program_batch_id'] = $rubric?->program_batch_id
                ?? $data['program_batch_id']
                ?? app(ProgramContextService::class)->activeBatchId($actor);

            $rubric ??= new Rubric(['created_by' => $actor->id]);
            $rubric->fill([...$data, 'updated_by' => $actor->id])->save();
            $rubric->criteria()->delete();
            foreach ($criteria as $index => $item) {
                $criterion = $rubric->criteria()->create(['name' => $item['name'], 'description' => $item['description'] ?? null, 'weight' => $item['weight'], 'sort_order' => $index + 1]);
                foreach (self::LABELS as $level => $label) {
                    $criterion->levels()->create(['level' => $level, 'label' => $label, 'description' => $item['levels'][$level - 1]]);
                }
            }

            return $rubric->refresh()->load('criteria.levels');
        });
    }
}
