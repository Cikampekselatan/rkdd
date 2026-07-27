<?php

namespace App\Enums;

enum AssignmentQuestionType: string
{
    case ShortText = 'short_text';
    case Paragraph = 'paragraph';
    case Url = 'url';
    case MultipleChoice = 'multiple_choice';

    public function label(): string
    {
        return match ($this) {
            self::ShortText => 'Jawaban singkat',
            self::Paragraph => 'Paragraf',
            self::Url => 'URL / tautan',
            self::MultipleChoice => 'Pilihan ganda',
        };
    }
}
