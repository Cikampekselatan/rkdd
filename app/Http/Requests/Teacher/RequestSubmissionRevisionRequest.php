<?php

namespace App\Http\Requests\Teacher;

use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;

class RequestSubmissionRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $s = $this->route('submission');

        return $s instanceof Submission && $this->user()?->can('review', $s) === true;
    }

    public function rules(): array
    {
        return ['revision_note' => ['required', 'string', 'min:5', 'max:5000']];
    }
}
