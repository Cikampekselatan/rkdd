<?php

namespace App\Models;

use Database\Factories\LearningModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningModule extends Model
{
    /** @use HasFactory<LearningModuleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'program_batch_id',
        'module_number',
        'title',
        'slug',
        'description',
        'sort_order',
        'is_active',
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

    public function sessions(): HasMany
    {
        return $this->hasMany(LearningSession::class)->orderBy('session_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'module_number' => 'integer',
            'program_batch_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
