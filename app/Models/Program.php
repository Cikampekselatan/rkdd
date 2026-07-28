<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'primary_color',
        'secondary_color',
        'accent_color',
        'logo_path',
        'banner_path',
        'is_active',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(ProgramBatch::class);
    }

    public function firstBatch(): HasOne
    {
        return $this->hasOne(ProgramBatch::class)->oldestOfMany();
    }

    public function portfolioWorkTypeOptions(): HasMany
    {
        return $this->hasMany(PortfolioWorkTypeOption::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
