<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyStudentAssessment extends Model
{
    public const COMPONENTS = [
        'product_portfolio_score' => ['label' => 'Produk dan portofolio', 'weight' => 35],
        'process_creativity_score' => ['label' => 'Proses, kreativitas, pemecahan masalah', 'weight' => 25],
        'collaboration_responsibility_score' => ['label' => 'Kolaborasi dan tanggung jawab', 'weight' => 15],
        'presentation_communication_score' => ['label' => 'Presentasi dan komunikasi', 'weight' => 15],
        'ethics_security_reflection_score' => ['label' => 'Etika, sumber, keamanan, refleksi', 'weight' => 10],
    ];

    protected $fillable = [
        'academic_year_id', 'program_batch_id', 'class_id', 'user_id', 'semester', 'assessment_month', 'period_label',
        'product_summary', 'evidence_url', 'product_portfolio_score', 'process_creativity_score',
        'collaboration_responsibility_score', 'presentation_communication_score',
        'ethics_security_reflection_score', 'final_score', 'achievement_level', 'strengths',
        'improvement_targets', 'remedial_plan', 'enrichment_plan', 'teacher_note',
        'is_published', 'published_at', 'assessed_by', 'assessed_at',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function programBatch(): BelongsTo
    {
        return $this->belongsTo(ProgramBatch::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public static function finalScoreFrom(array $data): float
    {
        return collect(self::COMPONENTS)->reduce(
            fn (float $score, array $component, string $field): float => $score + (((float) ($data[$field] ?? 0)) * $component['weight'] / 100),
            0.0,
        );
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

    public static function periodLabel(AcademicYear $year, int $semester, int $assessmentMonth): string
    {
        $offset = (($semester - 1) * 6) + ($assessmentMonth - 1);

        return $year->starts_on->copy()->addMonths($offset)->translatedFormat('F Y');
    }

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'program_batch_id' => 'integer',
            'assessment_month' => 'integer',
            'product_portfolio_score' => 'decimal:2',
            'process_creativity_score' => 'decimal:2',
            'collaboration_responsibility_score' => 'decimal:2',
            'presentation_communication_score' => 'decimal:2',
            'ethics_security_reflection_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'achievement_level' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'assessed_at' => 'datetime',
        ];
    }
}
