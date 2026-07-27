<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['academic_year_id', 'program_batch_id', 'class_id', 'learning_session_id', 'created_by', 'title', 'body', 'audience', 'priority', 'published_at', 'expires_at', 'is_pinned', 'is_published'];

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

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')->withPivot('read_at');
    }

    public function scopeVisible($query): mixed
    {
        return $query->where('is_published', true)->where(function ($q): void {
            $q->whereNull('published_at')->orWhere('published_at', '<=', now());
        })->where(function ($q): void {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    protected function casts(): array
    {
        return ['program_batch_id' => 'integer', 'audience' => AnnouncementAudience::class, 'priority' => AnnouncementPriority::class, 'published_at' => 'datetime', 'expires_at' => 'datetime', 'is_pinned' => 'boolean', 'is_published' => 'boolean'];
    }
}
