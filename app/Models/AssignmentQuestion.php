<?php

namespace App\Models;

use App\Enums\AssignmentQuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentQuestion extends Model
{
    protected $fillable = ['assignment_id', 'sort_order', 'prompt', 'help_text', 'answer_type', 'options', 'is_required'];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class);
    }

    protected function casts(): array
    {
        return ['answer_type' => AssignmentQuestionType::class, 'options' => 'array', 'is_required' => 'boolean', 'sort_order' => 'integer'];
    }
}
