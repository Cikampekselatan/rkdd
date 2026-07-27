<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecordLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attendance_record_id', 'user_id', 'event', 'old_status', 'new_status',
        'old_notes', 'new_notes', 'reason', 'created_at',
    ];

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'old_status' => AttendanceStatus::class,
            'new_status' => AttendanceStatus::class,
            'created_at' => 'datetime',
        ];
    }
}
