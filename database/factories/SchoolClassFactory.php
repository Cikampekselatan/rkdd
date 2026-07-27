<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'program_batch_id' => ProgramBatch::query()->where('slug', 'skuad-2026-2027')->value('id'),
            'name' => 'Kelompok SKUAD '.fake()->unique()->numerify('###'),
            'code' => 'SKUAD-'.fake()->unique()->numerify('###'),
            'grade_level' => null,
            'homeroom_teacher_id' => null,
            'capacity' => 32,
            'is_active' => true,
        ];
    }
}
