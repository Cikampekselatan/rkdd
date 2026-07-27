<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleSlug;
use App\Models\User;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageStaff', User::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
            'role' => ['required', Rule::in(array_map(fn (RoleSlug $role): string => $role->value, [RoleSlug::Admin, RoleSlug::Teacher, RoleSlug::Coach, RoleSlug::Principal]))],
            'program_batch_ids' => ['required', 'array', 'min:1'],
            'program_batch_ids.*' => ['integer', 'distinct', 'exists:program_batches,id'],
            'employee_number' => ['nullable', 'string', 'max:50', 'unique:teacher_profiles,employee_number'],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('role') === RoleSlug::Admin->value && ! $this->user()?->hasRole(RoleSlug::SuperAdmin)) {
                $validator->errors()->add('role', 'Hanya super-admin yang dapat membuat admin sekolah.');
            }

            $this->validateProgramScope($validator);
        }];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah digunakan akun lain. Gunakan email berbeda, atau edit akun yang sudah ada.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama. Isi kedua kolom password dengan teks yang sama persis.',
            'password.required' => 'Kata sandi wajib diisi untuk staff baru.',
            'program_batch_ids.required' => 'Pilih minimal satu program penempatan staff.',
            'program_batch_ids.min' => 'Pilih minimal satu program penempatan staff.',
            'employee_number.unique' => 'Nomor pegawai ini sudah digunakan staff lain.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    private function validateProgramScope(Validator $validator): void
    {
        $user = $this->user();

        if (! $user || $validator->errors()->has('program_batch_ids')) {
            return;
        }

        $allowedIds = app(ProgramContextService::class)
            ->availableBatches($user)
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();

        $selectedIds = collect($this->input('program_batch_ids', []))
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        if (array_diff($selectedIds, $allowedIds) !== []) {
            $validator->errors()->add('program_batch_ids', 'Staff hanya dapat ditempatkan pada program yang boleh Anda kelola.');
        }
    }
}
