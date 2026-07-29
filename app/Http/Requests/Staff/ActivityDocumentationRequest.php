<?php

namespace App\Http\Requests\Staff;

use App\Models\ActivityDocumentation;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ActivityDocumentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $documentation = $this->route('activity_documentation');

        return $documentation instanceof ActivityDocumentation
            ? $this->user()?->can('update', $documentation) === true
            : $this->user()?->can('create', ActivityDocumentation::class) === true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', Rule::in(app(ProgramContextService::class)->academicYears($this->user(), ['id'])->pluck('id')->all())],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'resource_url' => ['nullable', 'url:http,https', 'max:2048'],
            'video_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $documentation = $this->route('activity_documentation');
            $hasExistingPhoto = $documentation instanceof ActivityDocumentation && filled($documentation->photo_path);

            if (! $this->hasFile('photo') && ! $hasExistingPhoto && blank($this->input('resource_url')) && blank($this->input('video_url'))) {
                $validator->errors()->add('photo', 'Isi minimal satu dokumentasi: upload foto, URL dokumentasi, atau URL video.');
            }
        }];
    }
}
