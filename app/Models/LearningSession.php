<?php

namespace App\Models;

use App\Enums\LearningSessionStatus;
use Database\Factories\LearningSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningSession extends Model
{
    /** @use HasFactory<LearningSessionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'program_batch_id',
        'learning_module_id',
        'session_number',
        'semester',
        'title',
        'slug',
        'duration_minutes',
        'objectives',
        'introduction',
        'summary',
        'practice_instructions',
        'reflection_prompt',
        'status',
        'scheduled_at',
        'published_at',
        'published_by',
        'created_by',
        'updated_by',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(LearningModule::class, 'learning_module_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class)->orderBy('sort_order');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(StudentLearningProgress::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'session_number' => 'integer',
            'program_batch_id' => 'integer',
            'semester' => 'integer',
            'duration_minutes' => 'integer',
            'objectives' => 'array',
            'status' => LearningSessionStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
