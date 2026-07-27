<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGroupMember extends Model
{
    protected $fillable = ['project_group_id', 'user_id', 'role', 'contribution_note', 'joined_at', 'left_at', 'is_active'];

    public function projectGroup(): BelongsTo
    {
        return $this->belongsTo(ProjectGroup::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return ['joined_at' => 'date', 'left_at' => 'date', 'is_active' => 'boolean'];
    }
}
