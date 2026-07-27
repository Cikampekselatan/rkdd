<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Validation\Rule;

class AccessStepRequest extends OnboardingStepRequest
{
    public function rules(): array
    {
        return [
            'device_access' => ['required', 'array', 'min:1'],
            'device_access.*' => ['string', Rule::in(['android', 'iphone', 'laptop', 'desktop', 'shared', 'none'])],
            'internet_access' => ['required', Rule::in(['stable', 'limited', 'mobile_data', 'none'])],
            'willing_to_share_device' => ['required', 'boolean'],
            'digital_apps' => ['nullable', 'array', 'max:12'],
            'digital_apps.*' => ['string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'willing_to_share_device' => $this->boolean('willing_to_share_device'),
            'digital_apps' => collect(explode(',', (string) $this->input('digital_apps_text')))
                ->map(fn (string $app): string => trim($app))
                ->filter()
                ->values()
                ->all(),
        ]);
    }
}
