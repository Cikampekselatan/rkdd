<?php

namespace App\Models;

use App\Enums\TeacherActivityStatus;
use Database\Factories\TeacherActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherActivityLog extends Model
{
    /** @use HasFactory<TeacherActivityLogFactory> */
    use HasFactory;

    protected $fillable = ['academic_year_id', 'program_batch_id', 'teacher_id', 'log_number', 'activity_date', 'material', 'activities', 'assignment', 'signature_path', 'signature_original_name', 'reviewer_signature_path', 'reviewer_signature_original_name', 'status', 'submitted_at', 'verified_by', 'verified_at', 'rejection_note'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(TeacherActivityLogAudit::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [TeacherActivityStatus::Draft, TeacherActivityStatus::Rejected], true);
    }

    protected function casts(): array
    {
        return ['program_batch_id' => 'integer', 'activity_date' => 'date', 'status' => TeacherActivityStatus::class, 'submitted_at' => 'datetime', 'verified_at' => 'datetime'];
    }
}
