<?php

namespace App\Http\Requests\Staff;

use App\Enums\ImportantNotePriority;
use App\Enums\ImportantNoteStatus;
use App\Models\ImportantNote;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportantNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('important_note');

        return $note ? $this->user()?->can('update', $note) === true : $this->user()?->can('create', ImportantNote::class) === true;
    }

    public function rules(): array
    {
        return ['academic_year_id' => ['required', Rule::in(app(ProgramContextService::class)->academicYears($this->user(), ['id'])->pluck('id')->all())], 'note_date' => ['required', 'date', 'before_or_equal:today'], 'note' => ['required', 'string', 'max:20000'], 'resolution' => ['nullable', 'required_if:status,resolved', 'string', 'max:20000'], 'priority' => ['required', Rule::enum(ImportantNotePriority::class)], 'status' => ['required', Rule::in([ImportantNoteStatus::Open->value, ImportantNoteStatus::InProgress->value, ImportantNoteStatus::Resolved->value])]];
    }
}
