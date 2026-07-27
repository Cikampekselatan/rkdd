<?php

namespace App\Models;

use Database\Factories\RubricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rubric extends Model
{
    /** @use HasFactory<RubricFactory> */
    use HasFactory,SoftDeletes;

    protected $fillable = ['academic_year_id', 'program_batch_id', 'name', 'description', 'is_active', 'created_by', 'updated_by'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    protected function casts(): array
    {
        return ['program_batch_id' => 'integer', 'is_active' => 'boolean'];
    }
}
