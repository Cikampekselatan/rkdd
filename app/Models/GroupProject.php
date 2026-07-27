<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GroupProject extends Model
{
    protected $fillable = ['project_group_id', 'title', 'description', 'evidence_url', 'due_at', 'status', 'is_published', 'created_by', 'updated_by'];

    public function projectGroup(): BelongsTo
    {
        return $this->belongsTo(ProjectGroup::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(GroupProjectAssessment::class);
    }

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'is_published' => 'boolean'];
    }
}
