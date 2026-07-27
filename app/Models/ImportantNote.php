<?php

namespace App\Models;

use App\Enums\ImportantNotePriority;
use App\Enums\ImportantNoteStatus;
use Database\Factories\ImportantNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportantNote extends Model
{
    /** @use HasFactory<ImportantNoteFactory> */
    use HasFactory;

    protected $fillable = ['academic_year_id', 'program_batch_id', 'note_date', 'note', 'resolution', 'priority', 'status', 'created_by', 'updated_by', 'teacher_initial_path', 'teacher_initialed_by', 'teacher_initialed_at', 'coach_initial_path', 'coach_initialed_by', 'coach_initialed_at', 'verified_at'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function teacherInitialer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_initialed_by');
    }

    public function coachInitialer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_initialed_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ImportantNoteAudit::class);
    }

    protected function casts(): array
    {
        return ['program_batch_id' => 'integer', 'note_date' => 'date', 'priority' => ImportantNotePriority::class, 'status' => ImportantNoteStatus::class, 'teacher_initialed_at' => 'datetime', 'coach_initialed_at' => 'datetime', 'verified_at' => 'datetime'];
    }
}
