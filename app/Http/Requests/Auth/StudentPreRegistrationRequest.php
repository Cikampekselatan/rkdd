<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentPreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    public function rules(): array
    {
        $skills = ['design', 'photography', 'video', 'presentation', 'ai', 'coding', 'data', 'entrepreneurship'];

        return [
            'intended_program_batch_id' => ['nullable', 'integer', Rule::exists('program_batches', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'student_number' => ['required', 'string', 'max:50'],
            'nisn' => ['nullable', 'digits_between:8,20'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['required', 'date', 'before:today'],
            'grade_level' => ['required', 'integer', Rule::in([7, 8, 9])],
            'school_class_name' => ['required', 'string', 'max:50'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{8,30}$/'],
            'guardian_relationship' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'device_access' => ['required', 'array', 'min:1'],
            'device_access.*' => ['string', Rule::in(['android', 'iphone', 'laptop', 'desktop', 'shared', 'none'])],
            'internet_access' => ['required', Rule::in(['stable', 'limited', 'mobile_data', 'none'])],
            'willing_to_share_device' => ['required', 'boolean'],
            'digital_apps_text' => ['nullable', 'string', 'max:500'],
            'interests' => ['required', 'array', 'min:1'],
            'interests.*' => ['string', Rule::in($skills)],
            'initial_skills' => ['nullable', 'array'],
            'initial_skills.*' => ['string', Rule::in($skills)],
            'experience' => ['nullable', 'string', 'max:2000'],
            'expectation' => ['required', 'string', 'max:2000'],
            'learning_targets' => ['required', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'willing_to_share_device' => $this->boolean('willing_to_share_device'),
            'intended_program_batch_id' => $this->filled('intended_program_batch_id') ? $this->integer('intended_program_batch_id') : null,
        ]);
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key !== null || ! is_array($data)) {
            return $data;
        }

        $data['digital_apps'] = collect(explode(',', (string) ($data['digital_apps_text'] ?? '')))
            ->map(fn (string $app): string => trim($app))
            ->filter()
            ->values()
            ->all();

        unset($data['digital_apps_text']);

        return $data;
    }
}
