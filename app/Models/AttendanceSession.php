<?php

namespace App\Models;

use App\Enums\AttendanceSessionStatus;
use Database\Factories\AttendanceSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    /** @use HasFactory<AttendanceSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'learning_session_id', 'academic_year_id', 'program_batch_id', 'class_id', 'attendance_date', 'status',
        'notes', 'check_in_token_encrypted', 'check_in_token_hash', 'check_in_opens_at',
        'check_in_expires_at', 'check_in_enabled', 'opened_by', 'opened_at', 'closed_by', 'closed_at',
    ];

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }

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

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === AttendanceSessionStatus::Open;
    }

    public function hasActiveCheckIn(): bool
    {
        return $this->isOpen()
            && $this->check_in_enabled
            && $this->check_in_token_hash
            && (! $this->check_in_opens_at || $this->check_in_opens_at->isPast())
            && (! $this->check_in_expires_at || $this->check_in_expires_at->isFuture());
    }

    public function checkInUrl(): ?string
    {
        if (! $this->check_in_token_encrypted) {
            return null;
        }

        return route('student.attendance.check-in.show', [$this, $this->check_in_token_encrypted]);
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'program_batch_id' => 'integer',
            'status' => AttendanceSessionStatus::class,
            'check_in_token_encrypted' => 'encrypted',
            'check_in_enabled' => 'boolean',
            'check_in_opens_at' => 'datetime',
            'check_in_expires_at' => 'datetime',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
