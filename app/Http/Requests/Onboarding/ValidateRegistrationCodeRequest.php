<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;

class ValidateRegistrationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->status === UserStatus::Onboarding && ! $this->user()->isStaff();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
        ];
    }
}
