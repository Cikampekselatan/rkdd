<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\RoleSlug;
use Illuminate\Foundation\Http\FormRequest;

class LandingProfileVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleSlug::SuperAdmin) === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:700'],
            'video_url' => ['required', 'url', 'max:2048'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
