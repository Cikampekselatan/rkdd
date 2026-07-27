<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioWorkTypeOption extends Model
{
    protected $fillable = [
        'program_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
