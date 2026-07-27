<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PortfolioWorkTypeOption;
use App\Models\Program;
use App\Services\PortfolioWorkTypeOptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortfolioWorkTypeOptionController extends Controller
{
    public function index(Request $request): View
    {
        $programId = $request->integer('program_id') ?: null;
        $selectedProgram = $programId ? Program::query()->find($programId) : null;

        if ($selectedProgram) {
            app(PortfolioWorkTypeOptionService::class)->ensureDefaults($selectedProgram);
        }

        return view('super-admin.portfolio-work-types.index', [
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedProgramId' => $programId,
            'workTypes' => PortfolioWorkTypeOption::query()
                ->with('program:id,name')
                ->when($programId, fn ($query, int $id) => $query->where('program_id', $id))
                ->orderBy('program_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.portfolio-work-types.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        PortfolioWorkTypeOption::query()->create($data);

        return redirect()->route('super-admin.portfolio-work-types.index', ['program_id' => $data['program_id']])
            ->with('success', 'Jenis karya berhasil ditambahkan.');
    }

    public function edit(PortfolioWorkTypeOption $portfolioWorkType): View
    {
        return view('super-admin.portfolio-work-types.edit', [
            'workType' => $portfolioWorkType,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, PortfolioWorkTypeOption $portfolioWorkType): RedirectResponse
    {
        $data = $this->validated($request, $portfolioWorkType);
        $portfolioWorkType->update($data);

        return redirect()->route('super-admin.portfolio-work-types.index', ['program_id' => $data['program_id']])
            ->with('success', 'Jenis karya berhasil diperbarui.');
    }

    public function destroy(PortfolioWorkTypeOption $portfolioWorkType): RedirectResponse
    {
        $programId = $portfolioWorkType->program_id;
        $portfolioWorkType->delete();

        return redirect()->route('super-admin.portfolio-work-types.index', ['program_id' => $programId])
            ->with('success', 'Jenis karya berhasil dihapus.');
    }

    private function formData(): array
    {
        return [
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PortfolioWorkTypeOption $option = null): array
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name'), '_'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->filled('sort_order') ? $request->integer('sort_order') : 0,
        ]);

        return $request->validate([
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:140',
                'alpha_dash',
                Rule::unique('portfolio_work_type_options', 'slug')
                    ->where('program_id', $request->integer('program_id'))
                    ->ignore($option?->id),
            ],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
