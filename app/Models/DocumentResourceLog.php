<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentResourceLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['document_resource_id', 'user_id', 'event', 'context'];

    public function documentResource(): BelongsTo
    {
        return $this->belongsTo(DocumentResource::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }
}
