<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionVersion extends Model
{
    protected $fillable = ['submission_id', 'version_number', 'text_content', 'video_url', 'external_url', 'student_note', 'submitted_at'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class);
    }

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }
}
