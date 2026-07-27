<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\KnowledgeResourceRequest;
use App\Models\KnowledgeResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeResourceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'content_type' => ['nullable', 'string', 'max:30'],
        ]);

        return view('super-admin.knowledge-resources.index', [
            'resources' => KnowledgeResource::query()
                ->when($filters['q'] ?? null, fn ($query, string $q) => $query->where(fn ($inner) => $inner
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")))
                ->when($filters['content_type'] ?? null, fn ($query, string $type) => $query->where('content_type', $type))
                ->latest()
                ->orderByDesc('display_order')
                ->paginate(12)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('super-admin.knowledge-resources.form', ['resource' => new KnowledgeResource]);
    }

    public function store(KnowledgeResourceRequest $request): RedirectResponse
    {
        KnowledgeResource::query()->create($request->validated() + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('super-admin.knowledge-resources.index')->with('success', 'Konten Ruang Ilmu berhasil ditambahkan.');
    }

    public function edit(KnowledgeResource $knowledgeResource): View
    {
        return view('super-admin.knowledge-resources.form', ['resource' => $knowledgeResource]);
    }

    public function update(KnowledgeResourceRequest $request, KnowledgeResource $knowledgeResource): RedirectResponse
    {
        $knowledgeResource->update($request->validated() + ['updated_by' => $request->user()->id]);

        return redirect()->route('super-admin.knowledge-resources.index')->with('success', 'Konten Ruang Ilmu berhasil diperbarui.');
    }

    public function destroy(KnowledgeResource $knowledgeResource): RedirectResponse
    {
        $knowledgeResource->delete();

        return redirect()->route('super-admin.knowledge-resources.index')->with('success', 'Konten Ruang Ilmu berhasil diarsipkan.');
    }
}
