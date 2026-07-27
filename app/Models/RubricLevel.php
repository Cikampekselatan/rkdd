<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricLevel extends Model
{
    protected $fillable = ['rubric_criterion_id', 'level', 'label', 'description'];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }

    public function submissionScores(): HasMany
    {
        return $this->hasMany(SubmissionScore::class);
    }
}
