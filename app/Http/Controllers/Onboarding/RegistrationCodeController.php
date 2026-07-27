<?php

namespace App\Http\Controllers\Onboarding;

use App\Actions\RegistrationCodes\ValidateRegistrationCode;
use App\Enums\OnboardingStep;
use App\Enums\StudentMembershipStatus;
use App\Exceptions\RegistrationCodeRejected;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\ValidateRegistrationCodeRequest;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegistrationCodeController extends Controller
{
    public function show(): View
    {
        return view('onboarding.registration-code');
    }

    public function store(
        ValidateRegistrationCodeRequest $request,
        ValidateRegistrationCode $validateRegistrationCode,
    ): RedirectResponse {
        $request->session()->forget('onboarding.registration_code');

        try {
            $registrationCode = $validateRegistrationCode->execute((string) $request->string('code'));
        } catch (RegistrationCodeRejected $exception) {
            return back()
                ->withInput()
                ->withErrors(['code' => $exception->getMessage()]);
        }

        $draft = $request->session()->get('student.pre_registration');
        $intendedProgramBatchId = is_array($draft) ? ($draft['intended_program_batch_id'] ?? null) : null;

        if ($intendedProgramBatchId !== null && (int) $registrationCode->program_batch_id !== (int) $intendedProgramBatchId) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Kode pendaftaran tidak sesuai dengan tujuan program yang dipilih saat mengisi form. Pilih program yang benar atau minta kode sesuai program kepada admin/pembina.']);
        }

        $request->session()->put('onboarding.registration_code', [
            'user_id' => $request->user()->id,
            'registration_code_id' => $registrationCode->id,
            'validated_at' => now()->toIso8601String(),
        ]);

        $this->applyPreRegistrationDraft($request, $registrationCode);

        return redirect()->route('onboarding.registration-code.accepted');
    }

    public function accepted(Request $request): View|RedirectResponse
    {
        $state = $request->session()->get('onboarding.registration_code');

        if (! is_array($state) || ($state['user_id'] ?? null) !== $request->user()->id) {
            return redirect()
                ->route('onboarding.registration-code.show')
                ->withErrors(['code' => 'Validasi kode belum tersedia untuk akun ini.']);
        }

        return view('onboarding.registration-code-accepted', [
            'nextStep' => (int) ($request->user()->onboardingResponse()->value('current_step') ?? 1) >= OnboardingStep::Agreements->number()
                ? OnboardingStep::Agreements->value
                : OnboardingStep::Identity->value,
        ]);
    }

    private function applyPreRegistrationDraft(Request $request, RegistrationCode $registrationCode): void
    {
        $draft = $request->session()->get('student.pre_registration');

        if (! is_array($draft)) {
            return;
        }

        $user = $request->user();
        $resolvedClassId = $registrationCode->class_id;

        if ($resolvedClassId === null) {
            $groups = SchoolClass::query()
                ->where('academic_year_id', $registrationCode->academic_year_id)
                ->when($registrationCode->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('is_active', true)
                ->pluck('id');

            if ($groups->count() === 1) {
                $resolvedClassId = (int) $groups->first();
            }
        }

        $responseData = [
            'registration_code_id' => $registrationCode->id,
            'device_access' => $draft['device_access'] ?? [],
            'internet_access' => $draft['internet_access'] ?? null,
            'willing_to_share_device' => (bool) ($draft['willing_to_share_device'] ?? false),
            'digital_apps' => $draft['digital_apps'] ?? [],
            'interests' => $draft['interests'] ?? [],
            'initial_skills' => $draft['initial_skills'] ?? [],
            'experience' => $draft['experience'] ?? null,
            'expectation' => $draft['expectation'] ?? null,
            'learning_targets' => $draft['learning_targets'] ?? null,
        ];

        if ($resolvedClassId === null) {
            $user->onboardingResponse()->updateOrCreate(
                ['user_id' => $user->id],
                [...$responseData, 'current_step' => OnboardingStep::Identity->number()],
            );

            return;
        }

        $studentNumberBelongsToOtherUser = StudentProfile::query()
            ->where('student_number', $draft['student_number'] ?? '')
            ->where('user_id', '!=', $user->id)
            ->exists();
        $nisnBelongsToOtherUser = filled($draft['nisn'] ?? null) && StudentProfile::query()
            ->where('nisn', $draft['nisn'])
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($studentNumberBelongsToOtherUser || $nisnBelongsToOtherUser) {
            $user->onboardingResponse()->updateOrCreate(
                ['user_id' => $user->id],
                [...$responseData, 'current_step' => OnboardingStep::Identity->number()],
            );

            return;
        }

        DB::transaction(function () use ($draft, $responseData, $resolvedClassId, $request, $user): void {
            $user->update(['name' => $draft['name']]);
            $user->studentProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_number' => $draft['student_number'],
                    'nisn' => $draft['nisn'] ?? null,
                    'nickname' => $draft['nickname'] ?? null,
                    'gender' => $draft['gender'],
                    'birth_date' => $draft['birth_date'],
                    'grade_level' => $draft['grade_level'],
                    'school_class_name' => $draft['school_class_name'],
                    'class_id' => $resolvedClassId,
                    'parent_name' => $draft['parent_name'],
                    'parent_phone' => $draft['parent_phone'],
                    'guardian_relationship' => $draft['guardian_relationship'],
                    'address' => $draft['address'] ?? null,
                    'membership_status' => StudentMembershipStatus::Onboarding,
                ],
            );

            $user->onboardingResponse()->updateOrCreate(
                ['user_id' => $user->id],
                [...$responseData, 'current_step' => OnboardingStep::Agreements->number()],
            );

            $request->session()->forget('student.pre_registration');
        });
    }
}
