<?php

namespace App\Models;

use App\Enums\DocumentAudience;
use App\Enums\DocumentCategory;
use Database\Factories\DocumentResourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentResource extends Model
{
    /** @use HasFactory<DocumentResourceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'program_batch_id',
        'title',
        'slug',
        'category',
        'description',
        'drive_url',
        'drive_file_id',
        'preview_url',
        'audience',
        'semester',
        'sort_order',
        'is_pinned',
        'is_active',
        'published_at',
        'published_by',
        'created_by',
        'updated_by',
    ];

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

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DocumentResourceLog::class)->latest('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'program_batch_id' => 'integer',
            'audience' => DocumentAudience::class,
            'semester' => 'integer',
            'sort_order' => 'integer',
            'is_pinned' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
