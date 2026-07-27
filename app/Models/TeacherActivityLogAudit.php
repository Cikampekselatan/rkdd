<?php

namespace App\Models;

use App\Enums\TeacherActivityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherActivityLogAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['teacher_activity_log_id', 'user_id', 'event', 'old_status', 'new_status', 'context', 'created_at'];

    public function activityLog(): BelongsTo
    {
        return $this->belongsTo(TeacherActivityLog::class, 'teacher_activity_log_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return ['old_status' => TeacherActivityStatus::class, 'new_status' => TeacherActivityStatus::class, 'context' => 'array', 'created_at' => 'datetime'];
    }
}
