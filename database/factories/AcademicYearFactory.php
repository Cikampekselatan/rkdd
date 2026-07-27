<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        $startYear = 2100 + $this->faker->unique()->numberBetween(1, 900);

        return [
            'name' => $startYear.'/'.($startYear + 1),
            'starts_on' => "{$startYear}-07-01",
            'ends_on' => ($startYear + 1).'-06-30',
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => true]);
    }
}
