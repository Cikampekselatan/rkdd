<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Late = 'late';
    case UnderReview = 'under_review';
    case RevisionRequested = 'revision_requested';
    case Resubmitted = 'resubmitted';
    case Graded = 'graded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf', self::Submitted => 'Terkirim', self::Late => 'Terlambat', self::UnderReview => 'Sedang ditinjau', self::RevisionRequested => 'Perlu revisi', self::Resubmitted => 'Dikirim ulang', self::Graded => 'Dinilai'
        };
    }
}
