<?php

namespace App\Http\Controllers\Student;

use App\Actions\RegistrationCodes\ValidateRegistrationCode;
use App\Enums\StudentMembershipStatus;
use App\Exceptions\RegistrationCodeRejected;
use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgramEnrollmentController extends Controller
{
    public function index(Request $request, ProgramContextService $programContext): View
    {
        $memberships = $request->user()
            ->classMemberships()
            ->with(['programBatch.program', 'programBatch.institution', 'schoolClass', 'academicYear'])
            ->latest('joined_at')
            ->get();

        return view('student.programs.index', [
            'memberships' => $memberships,
            'activeProgramBatch' => $programContext->activeBatch($request->user()),
        ]);
    }

    public function join(Request $request, ValidateRegistrationCode $validateRegistrationCode): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        try {
            $registrationCode = $validateRegistrationCode->execute((string) $data['code']);
            $this->enroll($request, $registrationCode);
        } catch (RegistrationCodeRejected $exception) {
            return back()->withInput()->withErrors(['code' => $exception->getMessage()]);
        }

        return redirect()
            ->route('student.programs.index')
            ->with('success', 'Berhasil bergabung ke program baru. Kamu bisa mengganti program aktif dari pilihan di atas.');
    }

    private function enroll(Request $request, RegistrationCode $registrationCode): void
    {
        DB::transaction(function () use ($registrationCode, $request): void {
            $user = $request->user();
            $registrationCode = RegistrationCode::query()->lockForUpdate()->findOrFail($registrationCode->id);

            if ($registrationCode->hasReachedUsageLimit()) {
                throw new RegistrationCodeRejected('usage_limit', 'Batas penggunaan kode pendaftaran sudah tercapai.');
            }

            if ($registrationCode->program_batch_id === null) {
                throw new RegistrationCodeRejected('missing_program', 'Kode pendaftaran belum terhubung ke program.');
            }

            $alreadyJoined = ClassStudent::query()
                ->where('user_id', $user->id)
                ->where('program_batch_id', $registrationCode->program_batch_id)
                ->where('status', StudentMembershipStatus::Active->value)
                ->exists();

            if ($alreadyJoined) {
                throw new RegistrationCodeRejected('already_joined', 'Kamu sudah tergabung pada program ini.');
            }

            $classId = $registrationCode->class_id ?? $this->resolveSingleGroup($registrationCode);

            ClassStudent::query()->create([
                'academic_year_id' => $registrationCode->academic_year_id,
                'program_batch_id' => $registrationCode->program_batch_id,
                'class_id' => $classId,
                'user_id' => $user->id,
                'joined_at' => now(),
                'status' => StudentMembershipStatus::Active->value,
            ]);

            if ($user->studentProfile && $user->studentProfile->program_batch_id === null) {
                $user->studentProfile->update(['program_batch_id' => $registrationCode->program_batch_id]);
            }

            $registrationCode->increment('used_count');
            $request->session()->put(ProgramContextService::SESSION_KEY, $registrationCode->program_batch_id);
        }, 3);
    }

    private function resolveSingleGroup(RegistrationCode $registrationCode): int
    {
        $groups = SchoolClass::query()
            ->where('academic_year_id', $registrationCode->academic_year_id)
            ->where('program_batch_id', $registrationCode->program_batch_id)
            ->where('is_active', true)
            ->pluck('id');

        if ($groups->count() !== 1) {
            throw new RegistrationCodeRejected('group_required', 'Kode pendaftaran perlu diarahkan ke satu kelompok/angkatan.');
        }

        return (int) $groups->first();
    }
}
