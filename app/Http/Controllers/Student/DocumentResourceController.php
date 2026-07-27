<?php

namespace App\Http\Controllers\Student;

use App\Enums\DocumentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\DocumentIndexRequest;
use App\Models\DocumentResource;
use App\Services\DocumentAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DocumentResourceController extends Controller
{
    public function index(DocumentIndexRequest $request, DocumentAccessService $access): View
    {
        $filters = $request->safe()->only(['q', 'category', 'semester']);
        $category = DocumentCategory::tryFrom((string) ($filters['category'] ?? ''));

        if ($category !== null && $category->isStaffOnly()) {
            unset($filters['category']);
        }

        $resources = $access->queryFor($request->user())
            ->with('academicYear:id,name')
            ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['semester'] ?? null, fn (Builder $query, int|string $semester) => $query->where('semester', $semester))
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->paginate(12)
            ->withQueryString();

        return view('student.documents.index', [
            'resources' => $resources,
            'filters' => $filters,
            'categories' => DocumentCategory::studentLibraryCases(),
        ]);
    }

    public function show(DocumentResource $documentResource): View
    {
        $this->authorize('view', $documentResource);
        $documentResource->load('academicYear:id,name');

        return view('student.documents.show', compact('documentResource'));
    }
}
