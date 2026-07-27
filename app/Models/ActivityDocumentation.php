<?php

namespace App\Models;

use Database\Factories\ActivityDocumentationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityDocumentation extends Model
{
    /** @use HasFactory<ActivityDocumentationFactory> */
    use HasFactory;

    protected $fillable = ['academic_year_id', 'program_batch_id', 'created_by', 'activity_date', 'title', 'description', 'photo_path', 'photo_original_name', 'resource_url', 'video_url'];

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

    protected function casts(): array
    {
        return ['program_batch_id' => 'integer', 'activity_date' => 'date'];
    }
}
