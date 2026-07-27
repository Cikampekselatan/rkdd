<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'program_id',
        'institution_id',
        'name',
        'slug',
        'period_label',
        'starts_on',
        'ends_on',
        'audience_type',
        'participant_label',
        'is_active',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function registrationCodes(): HasMany
    {
        return $this->hasMany(RegistrationCode::class);
    }

    public function staffMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'program_batch_staff')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
