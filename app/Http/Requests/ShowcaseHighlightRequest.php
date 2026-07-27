<?php

namespace App\Http\Requests;

use App\Enums\ShowcaseHighlightPeriod;
use App\Enums\ShowcaseMediaType;
use App\Models\ShowcaseHighlight;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowcaseHighlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        $highlight = $this->route('showcase_highlight');

        return $highlight instanceof ShowcaseHighlight
            ? $this->user()?->can('update', $highlight) === true
            : $this->user()?->can('create', ShowcaseHighlight::class) === true;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', Rule::in(array_map(fn (ShowcaseHighlightPeriod $period): string => $period->value, ShowcaseHighlightPeriod::cases()))],
            'title' => ['required', 'string', 'max:160'],
            'student_name' => ['nullable', 'string', 'max:120'],
            'caption' => ['nullable', 'string', 'max:500'],
            'url' => ['required', 'url', 'max:2048'],
            'media_type' => ['nullable', Rule::in(array_map(fn (ShowcaseMediaType $type): string => $type->value, ShowcaseMediaType::cases()))],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $url = (string) $this->input('url');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'display_order' => $this->input('display_order', 0),
            'media_type' => $this->input('media_type') ?: ($url ? ShowcaseMediaType::detectFromUrl($url)->value : null),
        ]);
    }
}
