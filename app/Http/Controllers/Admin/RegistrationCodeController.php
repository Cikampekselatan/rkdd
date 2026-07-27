<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RegistrationCodes\CreateRegistrationCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRegistrationCodeRequest;
use App\Http\Requests\Admin\UpdateRegistrationCodeRequest;
use App\Models\AcademicYear;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationCodeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RegistrationCode::class);
        $activeBatchId = app(ProgramContextService::class)->activeBatchId(request()->user());

        return view('admin.registration-codes.index', [
            'registrationCodes' => RegistrationCode::query()
                ->with(['creator:id,name', 'academicYear:id,name', 'schoolClass:id,name', 'programBatch.program:id,name', 'programBatch.institution:id,name'])
                ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', RegistrationCode::class);

        return view('admin.registration-codes.create', $this->formData());
    }

    public function store(StoreRegistrationCodeRequest $request, CreateRegistrationCode $createRegistrationCode): RedirectResponse
    {
        $generated = $createRegistrationCode->execute($request->validated(), $request->user());

        return redirect()
            ->route('admin.registration-codes.index')
            ->with('success', 'Kode pendaftaran berhasil dibuat.')
            ->with('generated_code', $generated->plainText);
    }

    public function edit(RegistrationCode $registrationCode): View
    {
        $this->authorize('update', $registrationCode);

        return view('admin.registration-codes.edit', ['registrationCode' => $registrationCode, ...$this->formData()]);
    }

    public function update(UpdateRegistrationCodeRequest $request, RegistrationCode $registrationCode): RedirectResponse
    {
        $registrationCode->update($request->validated());

        return redirect()
            ->route('admin.registration-codes.index')
            ->with('success', 'Kode pendaftaran berhasil diperbarui.');
    }

    public function destroy(RegistrationCode $registrationCode): RedirectResponse
    {
        $this->authorize('delete', $registrationCode);

        if ($registrationCode->used_count > 0) {
            return back()->withErrors([
                'registration_code' => 'Kode yang sudah digunakan tidak dapat dihapus. Nonaktifkan kode sebagai gantinya.',
            ]);
        }

        $registrationCode->delete();

        return redirect()
            ->route('admin.registration-codes.index')
            ->with('success', 'Kode pendaftaran berhasil dihapus.');
    }

    private function formData(): array
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId(request()->user());

        return [
            'academicYears' => AcademicYear::query()->latest('starts_on')->get(['id', 'name', 'is_active']),
            'classes' => SchoolClass::query()->with('academicYear:id,name')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('grade_level')->orderBy('name')->get(),
        ];
    }
}
