<?php

namespace App\Http\Requests\Onboarding;

class GuardianStepRequest extends OnboardingStepRequest
{
    public function rules(): array
    {
        return [
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{8,30}$/'],
            'guardian_relationship' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
