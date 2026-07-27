<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectGroup extends Model
{
    protected $fillable = ['academic_year_id', 'program_batch_id', 'class_id', 'name', 'description', 'status', 'created_by', 'updated_by'];

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

    public function members(): HasMany
    {
        return $this->hasMany(ProjectGroupMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('is_active', true);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_group_members')
            ->withPivot(['role', 'contribution_note', 'joined_at', 'left_at', 'is_active'])
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(GroupProject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
