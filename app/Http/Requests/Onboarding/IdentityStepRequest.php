<?php

namespace App\Http\Requests\Onboarding;

use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IdentityStepRequest extends OnboardingStepRequest
{
    public function rules(): array
    {
        $profileId = $this->user()?->studentProfile?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'student_number' => ['required', 'string', 'max:50', Rule::unique('student_profiles')->ignore($profileId)],
            'nisn' => ['nullable', 'digits_between:8,20', Rule::unique('student_profiles')->ignore($profileId)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['required', 'date', 'before:today'],
            'grade_level' => ['required', 'integer', Rule::in([7, 8, 9])],
            'school_class_name' => ['required', 'string', 'max:50'],
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('is_active', true)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $state = $this->session()->get('onboarding.registration_code');
                $registrationCode = RegistrationCode::query()->find($state['registration_code_id'] ?? null);
                $group = SchoolClass::query()->find($this->integer('class_id'));

                if ($registrationCode?->class_id !== null && (int) $this->input('class_id') !== $registrationCode->class_id) {
                    $validator->errors()->add('class_id', 'Kelompok/angkatan harus sesuai dengan kode pendaftaran.');
                }

                if ($registrationCode && $group?->academic_year_id !== $registrationCode->academic_year_id) {
                    $validator->errors()->add('class_id', 'Kelompok/angkatan tidak berasal dari periode kode pendaftaran.');
                }

                if ($registrationCode?->program_batch_id !== null && $group?->program_batch_id !== $registrationCode->program_batch_id) {
                    $validator->errors()->add('class_id', 'Kelompok/angkatan tidak berasal dari program kode pendaftaran.');
                }
            },
        ];
    }
}
