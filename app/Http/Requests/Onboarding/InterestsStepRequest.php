<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Validation\Rule;

class InterestsStepRequest extends OnboardingStepRequest
{
    public function rules(): array
    {
        $skills = ['design', 'photography', 'video', 'presentation', 'ai', 'coding', 'data', 'entrepreneurship'];

        return [
            'interests' => ['required', 'array', 'min:1'],
            'interests.*' => ['string', Rule::in($skills)],
            'initial_skills' => ['nullable', 'array'],
            'initial_skills.*' => ['string', Rule::in($skills)],
            'experience' => ['nullable', 'string', 'max:2000'],
            'expectation' => ['required', 'string', 'max:2000'],
            'learning_targets' => ['required', 'string', 'max:2000'],
        ];
    }
}
