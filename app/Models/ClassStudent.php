<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassStudent extends Model
{
    protected $fillable = [
        'academic_year_id',
        'program_batch_id',
        'class_id',
        'user_id',
        'joined_at',
        'left_at',
        'status',
        'exit_reason',
        'exit_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    protected function casts(): array
    {
        return [
            'academic_year_id' => 'integer',
            'program_batch_id' => 'integer',
            'class_id' => 'integer',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
