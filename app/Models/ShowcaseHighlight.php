<?php

namespace App\Models;

use App\Enums\ShowcaseHighlightPeriod;
use App\Enums\ShowcaseMediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShowcaseHighlight extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'program_batch_id',
        'period',
        'title',
        'student_name',
        'caption',
        'url',
        'media_type',
        'display_order',
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

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function youtubeEmbedUrl(): ?string
    {
        $host = strtolower(parse_url($this->url, PHP_URL_HOST) ?? '');
        $path = trim(parse_url($this->url, PHP_URL_PATH) ?? '', '/');
        parse_str(parse_url($this->url, PHP_URL_QUERY) ?? '', $query);

        if (str_contains($host, 'youtu.be')) {
            return $path ? 'https://www.youtube-nocookie.com/embed/'.$path : null;
        }

        if (str_contains($host, 'youtube.com') && ! empty($query['v'])) {
            return 'https://www.youtube-nocookie.com/embed/'.$query['v'];
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'period' => ShowcaseHighlightPeriod::class,
            'program_batch_id' => 'integer',
            'media_type' => ShowcaseMediaType::class,
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
