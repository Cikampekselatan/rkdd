<?php

namespace App\Models;

use App\Enums\LearningMaterialType;
use Database\Factories\LearningMaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    /** @use HasFactory<LearningMaterialFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'learning_session_id',
        'type',
        'title',
        'content',
        'url',
        'sort_order',
        'is_required',
    ];

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }

    protected function casts(): array
    {
        return [
            'type' => LearningMaterialType::class,
            'sort_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }
}
