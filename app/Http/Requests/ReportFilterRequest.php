<?php

namespace App\Http\Requests;

use App\Enums\ReportType;
use App\Enums\RoleSlug;
use App\Models\Report;
use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = ReportType::tryFrom((string) $this->route('type'));

        return $type && $this->user()?->can('viewType', [Report::class, $type]) === true;
    }

    public function rules(): array
    {
        return ['year' => ['nullable', 'integer', 'exists:academic_years,id'], 'program_batch_id' => ['nullable', 'integer', 'exists:program_batches,id'], 'class' => ['nullable', 'integer', 'exists:classes,id'], 'semester' => ['nullable', Rule::in([1, 2])], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());
            $requestedBatchId = $this->filled('program_batch_id') ? $this->integer('program_batch_id') : null;
            if (! $this->user()?->hasRole(RoleSlug::SuperAdmin) && $requestedBatchId && $activeBatchId && $requestedBatchId !== $activeBatchId) {
                $validator->errors()->add('program_batch_id', 'Program laporan harus sesuai dengan program aktif.');
            }

            if ($this->filled('class') && $this->filled('year')) {
                $class = SchoolClass::find($this->integer('class'));
                if ($class && $class->academic_year_id !== $this->integer('year')) {
                    $validator->errors()->add('class', 'Kelas harus berasal dari tahun ajaran yang dipilih.');
                }

                $programBatchId = $this->user()?->hasRole(RoleSlug::SuperAdmin)
                    ? $requestedBatchId
                    : $activeBatchId;
                if ($class && $programBatchId && $class->program_batch_id && $class->program_batch_id !== $programBatchId) {
                    $validator->errors()->add('class', 'Kelas harus berasal dari program laporan yang dipilih.');
                }
            }
        }];
    }
}
