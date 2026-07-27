<?php

namespace App\Models;

use App\Services\GoogleDriveUrlParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeResource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'content_type',
        'thumbnail_url',
        'description',
        'resource_url',
        'display_order',
        'is_featured',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function typeLabel(): string
    {
        return match ($this->content_type) {
            'video' => 'Video tutorial',
            'article' => 'Artikel',
            'guide' => 'Panduan',
            default => 'eBook/Bacaan',
        };
    }

    public function typeIcon(): string
    {
        return match ($this->content_type) {
            'video' => 'bi-play-circle',
            'article' => 'bi-newspaper',
            'guide' => 'bi-map',
            default => 'bi-book',
        };
    }

    public function displayThumbnailUrl(): ?string
    {
        if (! filled($this->thumbnail_url)) {
            return null;
        }

        return app(GoogleDriveUrlParser::class)->thumbnailUrl($this->thumbnail_url)
            ?? $this->thumbnail_url;
    }

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
