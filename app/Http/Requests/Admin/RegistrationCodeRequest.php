<?php

namespace App\Http\Requests\Admin;

use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class RegistrationCodeRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'program_batch_id' => ['nullable', 'integer', 'exists:program_batches,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled(['starts_at', 'expires_at']) && $this->date('expires_at') <= $this->date('starts_at')) {
                    $validator->errors()->add('expires_at', 'Waktu kedaluwarsa harus setelah waktu mulai.');
                }

                if ($this->filled('class_id') && $this->filled('program_batch_id')) {
                    $classBatchId = SchoolClass::query()->whereKey($this->integer('class_id'))->value('program_batch_id');

                    if ((int) $classBatchId !== $this->integer('program_batch_id')) {
                        $validator->errors()->add('class_id', 'Kelompok/angkatan harus berada pada program aktif.');
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());

        $this->merge([
            'program_batch_id' => $activeBatchId,
            'is_active' => $this->boolean('is_active'),
            'class_id' => $this->filled('class_id') ? $this->input('class_id') : null,
            'max_uses' => $this->filled('max_uses') ? $this->input('max_uses') : null,
            'starts_at' => $this->input('starts_at') ?: null,
            'expires_at' => $this->input('expires_at') ?: null,
        ]);
    }
}
