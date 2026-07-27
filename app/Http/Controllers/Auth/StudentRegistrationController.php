<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StudentPreRegistrationRequest;
use App\Models\ProgramBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentRegistrationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectAuthenticatedUser($request)) {
            return $redirect;
        }

        return view('auth.student-register', [
            'draft' => $request->session()->get('student.pre_registration'),
            'programBatches' => $this->programBatches(),
        ]);
    }

    private function programBatches()
    {
        return ProgramBatch::query()
            ->where('is_active', true)
            ->with(['program:id,name,type,primary_color', 'institution:id,name'])
            ->orderBy('name')
            ->get();
    }

    public function store(StudentPreRegistrationRequest $request): RedirectResponse
    {
        $request->session()->put('student.pre_registration', [
            ...$request->validated(),
            'completed_at' => now()->toIso8601String(),
        ]);

        return redirect()
            ->route('student.register')
            ->with('success', 'Form profil sudah lengkap. Sekarang lanjutkan dengan Google, lalu masukkan kode pendaftaran dari pembina/admin.');
    }

    public function reset(Request $request): RedirectResponse
    {
        if ($redirect = $this->redirectAuthenticatedUser($request)) {
            return $redirect;
        }

        $request->session()->forget('student.pre_registration');

        return redirect()->route('student.register')->with('success', 'Draft pendaftaran dihapus. Silakan isi ulang form.');
    }

    private function redirectAuthenticatedUser(Request $request): ?RedirectResponse
    {
        $user = $request->user();

        if ($user?->hasRole(RoleSlug::Student) && $user->status === UserStatus::Active) {
            return redirect()->route('student.dashboard');
        }

        if ($user && ! $user->isStaff() && $user->status === UserStatus::Onboarding) {
            $state = $request->session()->get('onboarding.registration_code');

            if (is_array($state) && ($state['user_id'] ?? null) === $user->id) {
                return redirect()->route('onboarding.registration-code.accepted');
            }

            return redirect()->route('onboarding.registration-code.show');
        }

        if ($user?->isStaff()) {
            return redirect()->route($user->dashboardRouteName());
        }

        return null;
    }
}
