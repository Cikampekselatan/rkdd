<?php

namespace App\Http\Controllers\Onboarding;

use App\Actions\Onboarding\FinalizeStudentOnboarding;
use App\Enums\OnboardingStep;
use App\Enums\StudentMembershipStatus;
use App\Exceptions\OnboardingFinalizationRejected;
use App\Exceptions\RegistrationCodeRejected;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\AccessStepRequest;
use App\Http\Requests\Onboarding\AgreementsStepRequest;
use App\Http\Requests\Onboarding\GuardianStepRequest;
use App\Http\Requests\Onboarding\IdentityStepRequest;
use App\Http\Requests\Onboarding\InterestsStepRequest;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\StudentOnboardingResponse;
use App\Support\StudentAgreementRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WizardController extends Controller
{
    public function show(Request $request, string $step): View|RedirectResponse
    {
        $onboardingStep = OnboardingStep::tryFrom($step);
        abort_if($onboardingStep === null, 404);

        $response = $request->user()->onboardingResponse()->first();
        $availableStep = $response?->current_step ?? 1;

        if ($onboardingStep->number() > $availableStep) {
            return redirect()->route('onboarding.wizard.show', OnboardingStep::cases()[$availableStep - 1]->value);
        }

        $state = $request->session()->get('onboarding.registration_code', []);
        $registrationCode = RegistrationCode::query()->find(is_array($state) ? ($state['registration_code_id'] ?? null) : null);
        $groups = SchoolClass::query()
            ->where('academic_year_id', $registrationCode?->academic_year_id)
            ->when($registrationCode?->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('onboarding.wizard', [
            'step' => $onboardingStep,
            'profile' => $request->user()->studentProfile,
            'response' => $response,
            'groups' => $groups,
            'registrationCode' => $registrationCode,
            'preRegistration' => $request->session()->get('student.pre_registration', []),
            'agreementRules' => StudentAgreementRules::all(),
        ]);
    }

    public function updateIdentity(IdentityStepRequest $request): RedirectResponse
    {
        $this->ensureStepAccessible($request, OnboardingStep::Identity);
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $request->user()->update(['name' => $data['name']]);
            $request->user()->studentProfile()->updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'student_number' => $data['student_number'],
                    'nisn' => $data['nisn'] ?? null,
                    'nickname' => $data['nickname'] ?? null,
                    'gender' => $data['gender'],
                    'birth_date' => $data['birth_date'],
                    'grade_level' => $data['grade_level'],
                    'school_class_name' => $data['school_class_name'],
                    'class_id' => $data['class_id'],
                    'membership_status' => StudentMembershipStatus::Onboarding,
                ],
            );

            $this->advance($request, OnboardingStep::Guardian);
        });

        return $this->next(OnboardingStep::Guardian);
    }

    public function updateGuardian(GuardianStepRequest $request): RedirectResponse
    {
        $this->ensureStepAccessible($request, OnboardingStep::Guardian);

        $request->user()->studentProfile()->firstOrFail()->update($request->validated());
        $this->advance($request, OnboardingStep::Access);

        return $this->next(OnboardingStep::Access);
    }

    public function updateAccess(AccessStepRequest $request): RedirectResponse
    {
        $this->ensureStepAccessible($request, OnboardingStep::Access);

        $currentStep = (int) ($request->user()->onboardingResponse()->value('current_step') ?? 1);

        $request->user()->onboardingResponse()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                ...$request->safe()->only([
                    'device_access',
                    'internet_access',
                    'willing_to_share_device',
                    'digital_apps',
                ]),
                'current_step' => max($currentStep, OnboardingStep::Interests->number()),
            ],
        );

        return $this->next(OnboardingStep::Interests);
    }

    public function updateInterests(InterestsStepRequest $request): RedirectResponse
    {
        $this->ensureStepAccessible($request, OnboardingStep::Interests);

        $request->user()->onboardingResponse()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                ...$request->validated(),
                'current_step' => OnboardingStep::Agreements->number(),
            ],
        );

        return $this->next(OnboardingStep::Agreements);
    }

    public function finalize(
        AgreementsStepRequest $request,
        FinalizeStudentOnboarding $finalizeStudentOnboarding,
    ): RedirectResponse {
        $this->ensureStepAccessible($request, OnboardingStep::Agreements);
        $state = $request->session()->get('onboarding.registration_code', []);

        try {
            $finalizeStudentOnboarding->execute(
                $request->user(),
                is_array($state) ? $state : [],
                $request->validated(),
            );
        } catch (OnboardingFinalizationRejected|RegistrationCodeRejected $exception) {
            return back()->withErrors(['onboarding' => $exception->getMessage()]);
        }

        $request->session()->forget('onboarding.registration_code');

        return redirect()
            ->route('student.dashboard')
            ->with('success', 'Onboarding selesai. Selamat datang di SKUAD Learning Hub!');
    }

    private function advance(Request $request, OnboardingStep $step): StudentOnboardingResponse
    {
        $response = $request->user()->onboardingResponse()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['current_step' => 1],
        );

        if ($response->current_step < $step->number()) {
            $response->update(['current_step' => $step->number()]);
        }

        return $response;
    }

    private function ensureStepAccessible(Request $request, OnboardingStep $step): void
    {
        $currentStep = (int) ($request->user()->onboardingResponse()->value('current_step') ?? 1);
        abort_if($step->number() > $currentStep, 403);
    }

    private function next(OnboardingStep $step): RedirectResponse
    {
        return redirect()->route('onboarding.wizard.show', $step->value);
    }
}
