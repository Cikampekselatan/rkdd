<?php

namespace App\Http\Requests\Teacher;

use App\Models\PortfolioItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewPortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $i = $this->route('portfolio_item');

        return $i instanceof PortfolioItem && $this->user()?->can('approve', $i) === true;
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approved', 'rejected'])], 'approval_note' => ['nullable', 'required_if:decision,rejected', 'string', 'min:5', 'max:3000']];
    }
}
