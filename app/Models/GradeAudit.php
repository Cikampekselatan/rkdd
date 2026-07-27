<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['grade_id', 'user_id', 'event', 'before', 'after', 'created_at'];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
    }
}
