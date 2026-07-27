<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleSlug;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schoolClass = $this->route('school_class');

        return $schoolClass
            ? $this->user()?->can('update', $schoolClass) === true
            : $this->user()?->can('create', SchoolClass::class) === true;
    }

    public function rules(): array
    {
        $schoolClass = $this->route('school_class');
        $targetBatchId = $this->targetProgramBatchId();

        return [
            'program_batch_id' => ['nullable', 'integer', 'exists:program_batches,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('classes')
                    ->where('program_batch_id', $targetBatchId)
                    ->where('academic_year_id', $this->input('academic_year_id'))
                    ->ignore($schoolClass),
            ],
            'grade_level' => ['nullable'],
            'homeroom_teacher_id' => ['nullable', 'exists:users,id'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:300'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $schoolClass = $this->route('school_class');
            $targetBatchId = $this->targetProgramBatchId();

            if (! $this->programBatchIsAvailable($targetBatchId)) {
                $validator->errors()->add('program_batch_id', 'Pilih program/periode yang boleh Anda kelola.');
            }

            $groupExists = SchoolClass::query()
                ->where('program_batch_id', $targetBatchId)
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->when($schoolClass, fn ($query) => $query->whereKeyNot($schoolClass->id))
                ->exists();

            if ($groupExists) {
                $validator->errors()->add('academic_year_id', 'Setiap periode program hanya dapat memiliki satu kelompok/angkatan.');
            }

            if (! $this->filled('homeroom_teacher_id')) {
                return;
            }

            $isTeacherOrCoach = User::query()
                ->whereKey($this->input('homeroom_teacher_id'))
                ->whereHas('roles', fn ($query) => $query->whereIn('slug', [
                    RoleSlug::Teacher->value,
                    RoleSlug::Coach->value,
                ]))
                ->exists();

            if (! $isTeacherOrCoach) {
                $validator->errors()->add('homeroom_teacher_id', 'Koordinator kelompok harus memiliki role guru atau coach.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'program_batch_id' => $this->filled('program_batch_id') ? $this->integer('program_batch_id') : null,
            'homeroom_teacher_id' => $this->filled('homeroom_teacher_id') ? $this->input('homeroom_teacher_id') : null,
            'capacity' => $this->filled('capacity') ? $this->input('capacity') : null,
            'grade_level' => null,
        ]);
    }

    private function targetProgramBatchId(): ?int
    {
        $schoolClass = $this->route('school_class');

        return $this->integer('program_batch_id')
            ?: $schoolClass?->program_batch_id
            ?: app(ProgramContextService::class)->activeBatchId($this->user());
    }

    private function programBatchIsAvailable(?int $programBatchId): bool
    {
        if (! $this->user()) {
            return false;
        }

        $availableBatches = app(ProgramContextService::class)->availableBatches($this->user());

        if (! $programBatchId) {
            return $availableBatches->isEmpty();
        }

        return $availableBatches->contains('id', $programBatchId);
    }
}
