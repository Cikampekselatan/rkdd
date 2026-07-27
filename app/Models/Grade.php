<?php

namespace App\Models;

use App\Enums\RemedialStatus;
use Database\Factories\GradeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    /** @use HasFactory<GradeFactory> */
    use HasFactory;

    protected $fillable = ['submission_id', 'rubric_id', 'total_score', 'achievement_level', 'feedback', 'private_note', 'is_published', 'published_at', 'published_by', 'graded_by', 'remedial_status', 'remedial_note', 'remedial_due_at', 'remedial_response', 'remedial_submitted_at'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(SubmissionScore::class, 'submission_id', 'submission_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(GradeAudit::class);
    }

    protected function casts(): array
    {
        return ['total_score' => 'decimal:2', 'is_published' => 'boolean', 'published_at' => 'datetime', 'remedial_status' => RemedialStatus::class, 'remedial_due_at' => 'datetime', 'remedial_submitted_at' => 'datetime'];
    }
}
