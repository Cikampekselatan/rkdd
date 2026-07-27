<?php

namespace App\Http\Requests\Staff;

use App\Models\TeacherActivityLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TeacherActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $log = $this->route('teacher_activity_log');

        return $log ? $this->user()?->can('update', $log) === true : $this->user()?->can('create', TeacherActivityLog::class) === true;
    }

    public function rules(): array
    {
        $log = $this->route('teacher_activity_log');

        return ['academic_year_id' => ['required', 'exists:academic_years,id'], 'activity_date' => ['required', 'date', 'before_or_equal:today'], 'material' => ['required', 'string', 'max:5000'], 'activities' => ['required', 'string', 'max:20000'], 'assignment' => ['nullable', 'string', 'max:5000'], 'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'], 'signature_drawn' => ['nullable', 'string', 'max:300000', 'regex:/^data:image\/png;base64,/'], 'submit_now' => ['nullable', 'boolean']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $log = $this->route('teacher_activity_log');
            $duplicateDate = TeacherActivityLog::query()
                ->where('teacher_id', $this->user()?->id)
                ->whereDate('activity_date', $this->input('activity_date'))
                ->when($log, fn ($query) => $query->whereKeyNot($log->id))
                ->exists();

            if ($duplicateDate) {
                $validator->errors()->add('activity_date', 'Absen pengajar untuk tanggal ini sudah tersedia.');
            }

            if ($this->hasFile('signature') && filled($this->input('signature_drawn'))) {
                $validator->errors()->add('signature', 'Pilih salah satu: tanda tangan langsung atau unggah file.');
            }

            if ($this->boolean('submit_now') && ! $this->hasFile('signature') && blank($this->input('signature_drawn')) && ! $log?->signature_path) {
                $validator->errors()->add('signature', 'Tanda tangan wajib tersedia sebelum absen diajukan.');
            }
        }];
    }
}
