<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'attendance_session_id', 'user_id', 'status', 'notes', 'recorded_by', 'recorded_at',
        'checked_in_at', 'check_in_method',
    ];

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceRecordLog::class);
    }

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'recorded_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }
}
