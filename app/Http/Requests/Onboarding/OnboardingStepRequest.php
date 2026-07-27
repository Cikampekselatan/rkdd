<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;

abstract class OnboardingStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->status === UserStatus::Onboarding && ! $this->user()->isStaff();
    }
}
