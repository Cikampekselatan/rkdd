<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AcademicYears\SetActiveAcademicYear;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AcademicYear::class);

        return view('admin.academic-years.index', [
            'academicYears' => AcademicYear::query()->withCount('classes')->latest('starts_on')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AcademicYear::class);

        return view('admin.academic-years.create');
    }

    public function store(AcademicYearRequest $request, SetActiveAcademicYear $setActive): RedirectResponse
    {
        $academicYear = AcademicYear::query()->create($request->safe()->except('is_active'));

        if ($request->boolean('is_active') || ! AcademicYear::query()->where('is_active', true)->exists()) {
            $setActive->execute($academicYear);
        }

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil dibuat.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        $this->authorize('update', $academicYear);

        return view('admin.academic-years.edit', compact('academicYear'));
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear, SetActiveAcademicYear $setActive): RedirectResponse
    {
        $academicYear->update($request->safe()->except('is_active'));

        if ($request->boolean('is_active')) {
            $setActive->execute($academicYear);
        } elseif ($academicYear->is_active) {
            return back()->withErrors(['is_active' => 'Harus ada satu tahun ajaran aktif. Aktifkan tahun ajaran lain terlebih dahulu.']);
        }

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('delete', $academicYear);

        if ($academicYear->is_active || $academicYear->classes()->exists() || $academicYear->registrationCodes()->exists()) {
            return back()->withErrors(['academic_year' => 'Tahun ajaran aktif atau sudah digunakan tidak dapat dihapus.']);
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
