<?php

namespace App\Models;

use App\Enums\DiscussionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionTopic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['academic_year_id', 'program_batch_id', 'class_id', 'learning_session_id', 'created_by', 'title', 'body', 'status', 'is_pinned', 'is_hidden', 'hidden_by', 'hidden_at'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class, 'topic_id');
    }

    protected function casts(): array
    {
        return ['program_batch_id' => 'integer', 'status' => DiscussionStatus::class, 'is_pinned' => 'boolean', 'is_hidden' => 'boolean', 'hidden_at' => 'datetime'];
    }
}
