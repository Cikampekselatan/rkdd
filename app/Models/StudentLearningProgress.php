<?php

namespace App\Models;

use Database\Factories\StudentLearningProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLearningProgress extends Model
{
    /** @use HasFactory<StudentLearningProgressFactory> */
    use HasFactory;

    protected $table = 'student_learning_progress';

    protected $fillable = [
        'user_id',
        'learning_session_id',
        'progress_percent',
        'opened_at',
        'materials_completed_at',
        'exercise_completed_at',
        'assignment_completed_at',
        'reflection_completed_at',
        'completed_at',
        'last_accessed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'opened_at' => 'datetime',
            'materials_completed_at' => 'datetime',
            'exercise_completed_at' => 'datetime',
            'assignment_completed_at' => 'datetime',
            'reflection_completed_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }
}
