<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionScore extends Model
{
    protected $fillable = ['submission_id', 'rubric_criterion_id', 'rubric_level_id', 'level', 'weight', 'weighted_score', 'teacher_note'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }

    public function rubricLevel(): BelongsTo
    {
        return $this->belongsTo(RubricLevel::class);
    }

    protected function casts(): array
    {
        return ['weight' => 'decimal:2', 'weighted_score' => 'decimal:2'];
    }
}
