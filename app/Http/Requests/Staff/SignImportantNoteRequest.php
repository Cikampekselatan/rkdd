<?php

namespace App\Http\Requests\Staff;

use App\Models\ImportantNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SignImportantNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('important_note');

        return $note instanceof ImportantNote && $this->user()?->can('sign', $note) === true;
    }

    public function rules(): array
    {
        return ['initial' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'], 'initial_drawn' => ['nullable', 'string', 'max:300000', 'regex:/^data:image\/png;base64,/']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->hasFile('initial') && filled($this->input('initial_drawn'))) {
                $validator->errors()->add('initial', 'Pilih salah satu: paraf langsung atau unggah file.');
            }

            if (! $this->hasFile('initial') && blank($this->input('initial_drawn'))) {
                $validator->errors()->add('initial', 'Paraf wajib diisi dengan gambar langsung atau unggah file.');
            }
        }];
    }
}
