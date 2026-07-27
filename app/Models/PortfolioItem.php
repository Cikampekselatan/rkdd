<?php

namespace App\Models;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Services\PortfolioWorkTypeOptionService;
use Database\Factories\PortfolioItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioItem extends Model
{
    /** @use HasFactory<PortfolioItemFactory> */
    use HasFactory,SoftDeletes;

    protected $fillable = ['academic_year_id', 'program_batch_id', 'class_id', 'user_id', 'submission_id', 'initial_submission_version_id', 'final_submission_version_id', 'source_type', 'title', 'slug', 'work_type', 'description', 'reflection', 'sources', 'ai_used', 'ai_tools', 'ai_usage_description', 'thumbnail_path', 'initial_file_path', 'initial_original_name', 'final_file_path', 'final_original_name', 'initial_url', 'final_url', 'visibility', 'approval_status', 'approval_note', 'approved_by', 'approved_at', 'is_featured'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function initialVersion(): BelongsTo
    {
        return $this->belongsTo(SubmissionVersion::class, 'initial_submission_version_id');
    }

    public function finalVersion(): BelongsTo
    {
        return $this->belongsTo(SubmissionVersion::class, 'final_submission_version_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PortfolioItemAudit::class);
    }

    protected function casts(): array
    {
        return ['program_batch_id' => 'integer', 'visibility' => PortfolioVisibility::class, 'approval_status' => PortfolioApprovalStatus::class, 'ai_used' => 'boolean', 'is_featured' => 'boolean', 'approved_at' => 'datetime'];
    }

    public function workTypeLabel(): string
    {
        return app(PortfolioWorkTypeOptionService::class)->labelFor($this->work_type);
    }
}
