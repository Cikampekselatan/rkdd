<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Syllabus = 'silabus';
    case Module = 'modul';
    case Assessment = 'asesmen';
    case LessonPlan = 'rpp';
    case Curriculum = 'kurikulum';
    case ToolsMaterials = 'alat_dan_bahan';
    case TheoryBook = 'buku_teori';
    case AdministrationForm = 'form_administrasi';
    case Guide = 'panduan';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Syllabus => 'Silabus',
            self::Module => 'Modul',
            self::Assessment => 'Asesmen',
            self::LessonPlan => 'RPP',
            self::Curriculum => 'Kurikulum',
            self::ToolsMaterials => 'Alat dan Bahan',
            self::TheoryBook => 'Buku Teori',
            self::AdministrationForm => 'Form Administrasi',
            self::Guide => 'Panduan',
            self::Other => 'Lainnya',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Syllabus => 'bi-map',
            self::Module => 'bi-journal-richtext',
            self::Assessment => 'bi-clipboard-data',
            self::LessonPlan => 'bi-calendar2-week',
            self::Curriculum => 'bi-diagram-3',
            self::ToolsMaterials => 'bi-tools',
            self::TheoryBook => 'bi-book',
            self::AdministrationForm => 'bi-ui-checks-grid',
            self::Guide => 'bi-compass',
            self::Other => 'bi-folder2-open',
        };
    }

    /** @return list<self> */
    public static function studentLibraryCases(): array
    {
        return [
            self::Module,
            self::ToolsMaterials,
            self::TheoryBook,
            self::Guide,
            self::Assessment,
            self::Other,
        ];
    }

    /** @return list<string> */
    public static function studentLibraryValues(): array
    {
        return array_map(fn (self $category): string => $category->value, self::studentLibraryCases());
    }

    public function isStudentLibrary(): bool
    {
        return in_array($this, self::studentLibraryCases(), true);
    }

    public function isStaffOnly(): bool
    {
        return ! $this->isStudentLibrary();
    }
}
