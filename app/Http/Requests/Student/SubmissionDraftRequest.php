<?php

namespace App\Http\Requests\Student;

use App\Enums\AssignmentQuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmissionDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('assignment')) === true;
    }

    public function rules(): array
    {
        $a = $this->route('assignment');

        return ['text_content' => ['nullable', 'string', 'max:50000'], 'video_url' => ['nullable', 'url:http,https', 'max:2048'], 'external_url' => ['nullable', 'url:http,https', 'max:2048'], 'student_note' => ['nullable', 'string', 'max:5000'], 'answers' => ['nullable', 'array'], 'answers.*.question_id' => ['required', 'integer', Rule::exists('assignment_questions', 'id')->where('assignment_id', $a->id)], 'answers.*.answer_text' => ['nullable', 'string', 'max:50000'], 'answers.*.answer_url' => ['nullable', 'url:http,https', 'max:2048'], 'files' => ['nullable', 'array', 'max:'.$a->max_files], 'files.*' => ['file', 'max:'.$a->max_file_size_kb], 'remove_files' => ['nullable', 'array'], 'remove_files.*' => ['integer', 'exists:submission_files,id'], 'action' => ['required', 'in:draft,submit']];
    }

    public function after(): array
    {
        return [function (Validator $v): void {
            $a = $this->route('assignment');
            foreach ($this->file('files', []) as $file) {
                if ($a->allowed_mime_types && ! in_array($file->getMimeType(), $a->allowed_mime_types, true)) {
                    $v->errors()->add('files', 'Tipe file '.$file->getClientOriginalName().' tidak diizinkan.');
                }
            }
            if ($this->input('action') === 'submit') {
                $answers = collect($this->input('answers', []))->keyBy(fn (array $answer): int => (int) $answer['question_id']);
                foreach ($a->questions()->get() as $question) {
                    $answer = $answers->get($question->id, []);
                    if ($question->answer_type === AssignmentQuestionType::MultipleChoice && filled($answer['answer_text'] ?? null) && ! in_array($answer['answer_text'], $question->options ?? [], true)) {
                        $v->errors()->add('answers', 'Pilihan untuk pertanyaan "'.$question->prompt.'" tidak valid.');
                    }
                    if (! $question->is_required) {
                        continue;
                    }
                    $filled = filled($answer['answer_text'] ?? null) || filled($answer['answer_url'] ?? null);
                    if (! $filled) {
                        $v->errors()->add('answers', 'Pertanyaan wajib "'.$question->prompt.'" belum dijawab.');
                    }
                }
            }
        }];
    }
}
