<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionPost extends Model
{
    use HasFactory;

    protected $fillable = ['topic_id', 'user_id', 'parent_id', 'body', 'is_hidden', 'hidden_by', 'hidden_at'];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(DiscussionTopic::class, 'topic_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(DiscussionReport::class, 'post_id');
    }

    protected function casts(): array
    {
        return ['is_hidden' => 'boolean', 'hidden_at' => 'datetime'];
    }
}
