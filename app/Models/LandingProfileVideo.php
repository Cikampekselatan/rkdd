<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingProfileVideo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'thumbnail_url',
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

    public function youtubeEmbedUrl(): ?string
    {
        $host = strtolower(parse_url($this->video_url, PHP_URL_HOST) ?? '');
        $path = trim(parse_url($this->video_url, PHP_URL_PATH) ?? '', '/');
        parse_str(parse_url($this->video_url, PHP_URL_QUERY) ?? '', $query);

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
            'is_active' => 'boolean',
        ];
    }
}
