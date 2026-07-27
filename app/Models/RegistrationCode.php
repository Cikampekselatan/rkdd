<?php

namespace App\Models;

use Database\Factories\RegistrationCodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationCode extends Model
{
    /** @use HasFactory<RegistrationCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'program_batch_id',
        'academic_year_id',
        'class_id',
        'code_hash',
        'code_hint',
        'plain_code_encrypted',
        'max_uses',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $hidden = [
        'code_hash',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function onboardingResponses(): HasMany
    {
        return $this->hasMany(StudentOnboardingResponse::class);
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

    /**
     * @param  Builder<RegistrationCode>  $query
     * @return Builder<RegistrationCode>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'academic_year_id' => 'integer',
            'program_batch_id' => 'integer',
            'class_id' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'plain_code_encrypted' => 'encrypted:string',
        ];
    }
}
