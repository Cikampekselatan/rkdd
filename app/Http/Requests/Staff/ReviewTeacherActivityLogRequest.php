<?php

namespace App\Http\Requests\Staff;

use App\Models\TeacherActivityLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReviewTeacherActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $log = $this->route('teacher_activity_log');

        return $log instanceof TeacherActivityLog && $this->user()?->can('review', $log) === true;
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['verified', 'rejected'])], 'rejection_note' => ['nullable', 'required_if:decision,rejected', 'string', 'min:5', 'max:2000'], 'reviewer_signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'], 'reviewer_signature_drawn' => ['nullable', 'string', 'max:300000', 'regex:/^data:image\/png;base64,/']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->hasFile('reviewer_signature') && filled($this->input('reviewer_signature_drawn'))) {
                $validator->errors()->add('reviewer_signature', 'Pilih salah satu: tanda tangan langsung atau unggah file.');
            }

            if ($this->input('decision') === 'verified' && $this->user()?->hasRole('teacher') && ! $this->hasFile('reviewer_signature') && blank($this->input('reviewer_signature_drawn'))) {
                $validator->errors()->add('reviewer_signature', 'Tanda tangan Guru/Pembina wajib diisi sebelum absen instruktur/coach diteruskan ke admin dan kepala sekolah.');
            }
        }];
    }
}
