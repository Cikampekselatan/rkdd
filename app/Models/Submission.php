<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    protected $fillable = ['assignment_id', 'user_id', 'status', 'current_version_number', 'revision_count', 'submitted_at', 'last_reviewed_at', 'revision_note', 'revision_requested_by', 'revision_requested_at'];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SubmissionVersion::class)->orderBy('version_number');
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(SubmissionScore::class);
    }

    public function portfolioItem(): HasOne
    {
        return $this->hasOne(PortfolioItem::class);
    }

    public function currentVersion(): ?SubmissionVersion
    {
        return $this->versions->firstWhere('version_number', $this->current_version_number);
    }

    protected function casts(): array
    {
        return ['status' => SubmissionStatus::class, 'submitted_at' => 'datetime', 'last_reviewed_at' => 'datetime', 'revision_requested_at' => 'datetime'];
    }
}
