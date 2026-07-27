<?php

namespace App\Enums;

enum StudentExitReason: string
{
    case GradeNineSemesterTwo = 'grade_9_semester_2';
    case ChangedExtracurricular = 'changed_extracurricular';
    case OtherCommitment = 'other_commitment';
    case Personal = 'personal';

    public function label(): string
    {
        return match ($this) {
            self::GradeNineSemesterTwo => 'Kelas 9 semester 2',
            self::ChangedExtracurricular => 'Pindah kegiatan ekstrakurikuler',
            self::OtherCommitment => 'Kesibukan akademik atau kegiatan lain',
            self::Personal => 'Alasan pribadi lainnya',
        };
    }
}
