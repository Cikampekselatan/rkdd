<?php

namespace App\Models;

use App\Enums\AssignmentType;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['academic_year_id', 'program_batch_id', 'class_id', 'learning_session_id', 'rubric_id', 'title', 'instructions', 'type', 'available_from', 'due_at', 'allow_late', 'max_files', 'max_file_size_kb', 'allowed_mime_types', 'max_revisions', 'is_published', 'created_by', 'updated_by'];

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

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssignmentQuestion::class)->orderBy('sort_order');
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    protected function casts(): array
    {
        return ['program_batch_id' => 'integer', 'type' => AssignmentType::class, 'available_from' => 'datetime', 'due_at' => 'datetime', 'allow_late' => 'boolean', 'allowed_mime_types' => 'array', 'is_published' => 'boolean', 'max_files' => 'integer', 'max_file_size_kb' => 'integer', 'max_revisions' => 'integer'];
    }
}
