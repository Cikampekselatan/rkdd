<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\InstitutionRequest;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function index(): View
    {
        return view('super-admin.institutions.index', [
            'institutions' => Institution::query()->withCount('batches')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.institutions.create');
    }

    public function store(InstitutionRequest $request): RedirectResponse
    {
        Institution::query()->create($request->validated());

        return redirect()->route('super-admin.institutions.index')->with('success', 'Lembaga/penyelenggara berhasil dibuat.');
    }

    public function edit(Institution $institution): View
    {
        return view('super-admin.institutions.edit', compact('institution'));
    }

    public function update(InstitutionRequest $request, Institution $institution): RedirectResponse
    {
        $institution->update($request->validated());

        return redirect()->route('super-admin.institutions.index')->with('success', 'Lembaga/penyelenggara berhasil diperbarui.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        if ($institution->batches()->exists()) {
            return back()->withErrors(['institution' => 'Lembaga yang sudah memiliki batch/periode tidak dapat dihapus. Nonaktifkan lembaga jika tidak digunakan.']);
        }

        $institution->delete();

        return redirect()->route('super-admin.institutions.index')->with('success', 'Lembaga/penyelenggara berhasil dihapus.');
    }
}
