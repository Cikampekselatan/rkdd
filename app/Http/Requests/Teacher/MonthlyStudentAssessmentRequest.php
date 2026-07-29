<?php

namespace App\Http\Requests\Teacher;

use App\Enums\StudentMembershipStatus;
use App\Models\ClassStudent;
use App\Models\MonthlyStudentAssessment;
use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MonthlyStudentAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assessment = $this->route('monthly_assessment');

        return $assessment instanceof MonthlyStudentAssessment
            ? $this->user()?->can('update', $assessment) === true
            : $this->user()?->can('create', MonthlyStudentAssessment::class) === true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', Rule::in(app(ProgramContextService::class)->academicYears($this->user(), ['id'])->pluck('id')->all())],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'semester' => ['required', Rule::in([1, 2])],
            'assessment_month' => ['required', 'integer', 'min:1', 'max:6'],
            'product_summary' => ['nullable', 'string', 'max:3000'],
            'evidence_url' => ['nullable', 'url', 'max:2048'],
            'product_portfolio_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'process_creativity_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'collaboration_responsibility_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'presentation_communication_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'ethics_security_reflection_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'strengths' => ['nullable', 'string', 'max:4000'],
            'improvement_targets' => ['nullable', 'string', 'max:4000'],
            'remedial_plan' => ['nullable', 'string', 'max:4000'],
            'enrichment_plan' => ['nullable', 'string', 'max:4000'],
            'teacher_note' => ['nullable', 'string', 'max:4000'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $assessment = $this->route('monthly_assessment');
            $class = SchoolClass::query()->find($this->integer('class_id'));
            $activeBatchId = app(ProgramContextService::class)->activeBatchId($this->user());

            if ($class && $class->academic_year_id !== $this->integer('academic_year_id')) {
                $validator->errors()->add('class_id', 'Kelompok harus berasal dari tahun ajaran yang dipilih.');
            }

            if ($activeBatchId && $class?->program_batch_id && $class->program_batch_id !== $activeBatchId) {
                $validator->errors()->add('class_id', 'Kelompok harus berasal dari program aktif.');
            }

            $isMember = ClassStudent::query()
                ->where('academic_year_id', $this->integer('academic_year_id'))
                ->where('class_id', $this->integer('class_id'))
                ->when($class?->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('user_id', $this->integer('user_id'))
                ->where('status', StudentMembershipStatus::Active->value)
                ->exists();

            if (! $isMember) {
                $validator->errors()->add('user_id', 'Siswa harus anggota aktif pada kelompok dan tahun ajaran yang dipilih.');
            }

            $duplicate = MonthlyStudentAssessment::query()
                ->where('academic_year_id', $this->integer('academic_year_id'))
                ->when($class?->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('user_id', $this->integer('user_id'))
                ->where('semester', $this->integer('semester'))
                ->where('assessment_month', $this->integer('assessment_month'))
                ->when($assessment instanceof MonthlyStudentAssessment, fn ($query) => $query->whereKeyNot($assessment->id))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('assessment_month', 'Asesmen siswa untuk bulan dan semester ini sudah tersedia.');
            }
        }];
    }
}
