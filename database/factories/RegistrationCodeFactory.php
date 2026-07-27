<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\RegistrationCode;
use App\Models\User;
use App\Services\RegistrationCodeHasher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationCode>
 */
class RegistrationCodeFactory extends Factory
{
    public function definition(): array
    {
        $randomValue = bin2hex(random_bytes(20));

        return [
            'name' => 'Gelombang '.fake()->numberBetween(1, 5),
            'academic_year_id' => AcademicYear::factory(),
            'class_id' => null,
            'code_hash' => hash('sha256', $randomValue),
            'code_hint' => mb_strtoupper(mb_substr($randomValue, -8)),
            'plain_code_encrypted' => null,
            'max_uses' => 30,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function forPlainText(string $plainText): static
    {
        return $this->state(fn (array $attributes): array => [
            'code_hash' => app(RegistrationCodeHasher::class)->hash($plainText),
            'code_hint' => app(RegistrationCodeHasher::class)->hint($plainText),
            'plain_code_encrypted' => $plainText,
        ]);
    }
}
