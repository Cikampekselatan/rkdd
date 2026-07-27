<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupProjectAssessment extends Model
{
    protected $fillable = ['group_project_id', 'final_score', 'achievement_level', 'feedback', 'private_note', 'is_published', 'published_at', 'assessed_by'];

    public function groupProject(): BelongsTo
    {
        return $this->belongsTo(GroupProject::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public static function achievementLevel(float $score): int
    {
        return match (true) {
            $score >= 90 => 4,
            $score >= 75 => 3,
            $score >= 60 => 2,
            default => 1,
        };
    }

    public static function achievementLabel(int $level): string
    {
        return match ($level) {
            4 => 'Kreator Mandiri',
            3 => 'Terampil',
            2 => 'Berkembang',
            default => 'Perlu Pendampingan',
        };
    }

    protected function casts(): array
    {
        return ['final_score' => 'decimal:2', 'achievement_level' => 'integer', 'is_published' => 'boolean', 'published_at' => 'datetime'];
    }
}
