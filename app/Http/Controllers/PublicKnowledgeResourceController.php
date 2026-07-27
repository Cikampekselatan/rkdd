<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeResource;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicKnowledgeResourceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:30'],
        ]);

        return view('knowledge.index', [
            'resources' => KnowledgeResource::query()
                ->active()
                ->when($filters['q'] ?? null, fn ($query, string $q) => $query->where(fn ($inner) => $inner
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")))
                ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('content_type', $type))
                ->orderByDesc('is_featured')
                ->orderByDesc('display_order')
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }
}
