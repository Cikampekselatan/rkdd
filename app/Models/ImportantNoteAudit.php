<?php

namespace App\Models;

use App\Enums\ImportantNoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportantNoteAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['important_note_id', 'user_id', 'event', 'old_status', 'new_status', 'context', 'created_at'];

    public function importantNote(): BelongsTo
    {
        return $this->belongsTo(ImportantNote::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return ['old_status' => ImportantNoteStatus::class, 'new_status' => ImportantNoteStatus::class, 'context' => 'array', 'created_at' => 'datetime'];
    }
}
