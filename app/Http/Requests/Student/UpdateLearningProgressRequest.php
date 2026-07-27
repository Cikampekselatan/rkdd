<?php

namespace App\Http\Requests\Student;

use App\Models\LearningSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLearningProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('learning_session');

        return $session instanceof LearningSession && $this->user()?->can('view', $session) === true;
    }

    public function rules(): array
    {
        return [
            'component' => ['required', Rule::in(['materials', 'exercise', 'reflection'])],
        ];
    }
}
