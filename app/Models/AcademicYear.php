<?php

namespace App\Models;

use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_active'];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function registrationCodes(): HasMany
    {
        return $this->hasMany(RegistrationCode::class);
    }

    public function learningModules(): HasMany
    {
        return $this->hasMany(LearningModule::class);
    }

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class);
    }

    public function documentResources(): HasMany
    {
        return $this->hasMany(DocumentResource::class);
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
