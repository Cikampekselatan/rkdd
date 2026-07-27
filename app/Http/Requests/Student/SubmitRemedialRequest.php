<?php

namespace App\Http\Requests\Student;

use App\Models\Grade;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRemedialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $g = $this->route('grade');

        return $g instanceof Grade && $this->user()?->can('submitRemedial', $g) === true;
    }

    public function rules(): array
    {
        return ['remedial_response' => ['required', 'string', 'min:10', 'max:20000']];
    }
}
