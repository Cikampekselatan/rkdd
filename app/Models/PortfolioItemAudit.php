<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItemAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['portfolio_item_id', 'user_id', 'event', 'context', 'created_at'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PortfolioItem::class, 'portfolio_item_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }
}
