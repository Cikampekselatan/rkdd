<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ProjectGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectGroupController extends Controller
{
    public function index(Request $request): View
    {
        $groups = ProjectGroup::query()
            ->whereHas('activeMembers', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['academicYear:id,name', 'schoolClass:id,name'])
            ->withCount('projects')
            ->latest()
            ->paginate(12);

        return view('student.project-groups.index', compact('groups'));
    }

    public function show(Request $request, ProjectGroup $projectGroup): View
    {
        abort_unless($projectGroup->activeMembers()->where('user_id', $request->user()->id)->exists(), 403);

        $projectGroup->load([
            'academicYear:id,name',
            'schoolClass:id,name',
            'members.student:id,name,email',
            'projects' => fn ($query) => $query->where('is_published', true)->with('assessment')->latest(),
        ]);

        return view('student.project-groups.show', ['group' => $projectGroup]);
    }
}
