<?php

namespace App\Models;

use App\Enums\StudentMembershipStatus;
use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_number',
        'nisn',
        'nickname',
        'gender',
        'birth_date',
        'grade_level',
        'school_class_name',
        'class_id',
        'program_batch_id',
        'parent_name',
        'parent_phone',
        'guardian_relationship',
        'address',
        'joined_at',
        'membership_status',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'grade_level' => 'integer',
            'program_batch_id' => 'integer',
            'joined_at' => 'datetime',
            'membership_status' => StudentMembershipStatus::class,
        ];
    }
}
