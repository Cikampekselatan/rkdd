<?php

namespace App\Actions\RegistrationCodes;

use App\Data\GeneratedRegistrationCode;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\RegistrationCodeHasher;

class CreateRegistrationCode
{
    public function __construct(private readonly RegistrationCodeHasher $hasher) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $creator): GeneratedRegistrationCode
    {
        $attributes['program_batch_id'] ??= $this->resolveProgramBatchId($attributes);

        do {
            $plainText = $this->generatePlainText();
            $hash = $this->hasher->hash($plainText);
        } while (RegistrationCode::query()->where('code_hash', $hash)->exists());

        $registrationCode = RegistrationCode::query()->create([
            ...$attributes,
            'code_hash' => $hash,
            'code_hint' => $this->hasher->hint($plainText),
            'plain_code_encrypted' => $plainText,
            'used_count' => 0,
            'created_by' => $creator->id,
        ]);

        return new GeneratedRegistrationCode($registrationCode, $plainText);
    }

    private function generatePlainText(): string
    {
        $alphabet = (string) config('registration-codes.alphabet');
        $length = (int) config('registration-codes.length', 20);
        $characters = '';

        for ($index = 0; $index < $length; $index++) {
            $characters .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return (string) config('registration-codes.prefix', 'SKUAD').'-'.implode('-', str_split($characters, 5));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveProgramBatchId(array $attributes): ?int
    {
        if (! empty($attributes['class_id'])) {
            $classBatchId = SchoolClass::query()->whereKey($attributes['class_id'])->value('program_batch_id');

            if ($classBatchId) {
                return (int) $classBatchId;
            }
        }

        return ProgramBatch::query()->where('slug', 'skuad-2026-2027')->value('id')
            ?? ProgramBatch::query()->orderBy('id')->value('id');
    }
}
