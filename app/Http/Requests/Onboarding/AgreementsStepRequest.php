<?php

namespace App\Http\Requests\Onboarding;

class AgreementsStepRequest extends OnboardingStepRequest
{
    public function rules(): array
    {
        return [
            'agree_rules' => ['accepted'],
            'agree_privacy' => ['accepted'],
            'agree_ai_policy' => ['accepted'],
            'agree_publication_policy' => ['accepted'],
        ];
    }
}
