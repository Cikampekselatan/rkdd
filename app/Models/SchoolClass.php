<?php

namespace App\Models;

use Database\Factories\SchoolClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'academic_year_id',
        'program_batch_id',
        'name',
        'code',
        'grade_level',
        'homeroom_teacher_id',
        'capacity',
        'is_active',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'class_id');
    }

    public function classMemberships(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'class_id');
    }

    public function registrationCodes(): HasMany
    {
        return $this->hasMany(RegistrationCode::class, 'class_id');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'class_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_students', 'class_id', 'user_id')
            ->withPivot(['academic_year_id', 'joined_at', 'status'])
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'program_batch_id' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
