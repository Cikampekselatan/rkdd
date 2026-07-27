<?php

namespace Database\Factories;

use App\Enums\ImportantNotePriority;
use App\Enums\ImportantNoteStatus;
use App\Models\AcademicYear;
use App\Models\ImportantNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportantNote> */
class ImportantNoteFactory extends Factory
{
    public function definition(): array
    {
        return ['academic_year_id' => AcademicYear::factory(), 'note_date' => today(), 'note' => fake()->paragraph(), 'resolution' => null, 'priority' => ImportantNotePriority::Medium, 'status' => ImportantNoteStatus::Open, 'created_by' => null, 'updated_by' => null];
    }
}
