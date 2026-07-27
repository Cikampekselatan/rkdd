<?php

namespace App\Http\Requests\Teacher;

use App\Enums\RemedialStatus;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $s = $this->route('submission');

        return $s instanceof Submission && $this->user()?->can('view', $s) === true;
    }

    public function rules(): array
    {
        return ['scores' => ['required', 'array', 'min:1'], 'scores.*.criterion_id' => ['required', 'integer', 'distinct', 'exists:rubric_criteria,id'], 'scores.*.level' => ['required', 'integer', 'min:1', 'max:4'], 'scores.*.teacher_note' => ['nullable', 'string', 'max:2000'], 'feedback' => ['nullable', 'string', 'max:10000'], 'private_note' => ['nullable', 'string', 'max:10000'], 'action' => ['required', Rule::in(['draft', 'publish', 'revision'])], 'revision_note' => ['nullable', 'required_if:action,revision', 'string', 'min:5', 'max:5000'], 'remedial_status' => ['required', Rule::enum(RemedialStatus::class)], 'remedial_note' => ['nullable', 'required_if:remedial_status,assigned', 'string', 'max:5000'], 'remedial_due_at' => ['nullable', 'date', 'after:now']];
    }

    public function after(): array
    {
        return [function (Validator $v): void {
            $s = $this->route('submission');
            $rubric = $s?->assignment?->rubric;
            if (! $rubric) {
                $v->errors()->add('scores', 'Tugas belum memiliki rubrik.');

                return;
            }$expected = $rubric->criteria()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $actual = collect($this->input('scores', []))->pluck('criterion_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($expected !== $actual) {
                $v->errors()->add('scores', 'Skor harus memuat seluruh kriteria rubrik.');
            }
        }];
    }
}
