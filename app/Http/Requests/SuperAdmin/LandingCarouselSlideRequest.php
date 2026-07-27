<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\RoleSlug;
use Illuminate\Foundation\Http\FormRequest;

class LandingCarouselSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleSlug::SuperAdmin) === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:600'],
            'image_url' => ['required', 'url', 'max:2048'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'url', 'max:2048'],
            'display_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
