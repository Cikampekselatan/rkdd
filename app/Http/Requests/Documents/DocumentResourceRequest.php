<?php

namespace App\Http\Requests\Documents;

use App\Data\ParsedDriveUrl;
use App\Enums\DocumentAudience;
use App\Enums\DocumentCategory;
use App\Models\DocumentResource;
use App\Services\GoogleDriveUrlParser;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DocumentResourceRequest extends FormRequest
{
    private ?ParsedDriveUrl $parsedDriveUrl = null;

    public function authorize(): bool
    {
        $resource = $this->route('document_resource');

        return $resource
            ? $this->user()?->can('update', $resource) === true
            : $this->user()?->can('create', DocumentResource::class) === true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', Rule::in(app(ProgramContextService::class)->academicYears($this->user(), ['id'])->pluck('id')->all())],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'drive_url' => ['required', 'url:http,https', 'max:2048'],
            'audience' => ['required', Rule::enum(DocumentAudience::class)],
            'semester' => ['nullable', Rule::in([1, 2])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_pinned' => ['required', 'boolean'],
            'publish_now' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $category = DocumentCategory::tryFrom((string) $this->input('category'));
            $audience = DocumentAudience::tryFrom((string) $this->input('audience'));

            if ($category?->isStaffOnly() && in_array($audience, [DocumentAudience::Students, DocumentAudience::InternalPublic], true)) {
                $validator->errors()->add('category', 'Kategori ini khusus staff. Untuk siswa gunakan Modul, Alat dan Bahan, Buku Teori, Panduan, Asesmen, atau Lainnya.');
                $validator->errors()->add('audience', 'Dokumen RPP, kurikulum, silabus, dan administrasi tidak boleh dipublikasikan ke siswa.');
            }

            if ($validator->errors()->has('drive_url')) {
                return;
            }

            $this->parsedDriveUrl = app(GoogleDriveUrlParser::class)->parse((string) $this->input('drive_url'));

            if ($this->parsedDriveUrl === null) {
                $validator->errors()->add('drive_url', 'Gunakan URL Google Drive atau Google Docs yang memuat ID file valid.');
            }
        }];
    }

    public function parsedDriveUrl(): ParsedDriveUrl
    {
        return $this->parsedDriveUrl ?? app(GoogleDriveUrlParser::class)->parse((string) $this->input('drive_url'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'academic_year_id' => $this->filled('academic_year_id') ? $this->input('academic_year_id') : null,
            'semester' => $this->filled('semester') ? $this->input('semester') : null,
            'is_pinned' => $this->boolean('is_pinned'),
            'publish_now' => $this->boolean('publish_now'),
        ]);
    }
}
