<?php

namespace App\Http\Controllers\Interaction;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\DiscussionPostRequest;
use App\Http\Requests\DiscussionTopicRequest;
use App\Models\DiscussionPost;
use App\Models\DiscussionTopic;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Services\DiscussionService;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DiscussionTopic::class);
        $user = $request->user();
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($user);
        $topics = DiscussionTopic::with(['author:id,name,profile_photo_path', 'schoolClass:id,name', 'learningSession:id,title'])
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->withCount(['posts', 'posts as reports_count' => fn ($q) => $q->whereHas('reports', fn ($r) => $r->whereNull('resolved_at'))]);
        if ($user->hasRole(RoleSlug::Student)) {
            $topics->where('is_hidden', false)->whereIn('class_id', $user->classMemberships()->where('status', 'active')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->pluck('class_id'));
        }
        $topics = $topics->orderByDesc('is_pinned')->latest('updated_at')->paginate(15);

        return view('interactions.discussions.index', [
            'topics' => $topics,
            'classes' => SchoolClass::where('is_active', true)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('name')->get(),
            'sessions' => LearningSession::when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('session_number')->get(),
        ]);
    }

    public function store(DiscussionTopicRequest $request, DiscussionService $service): RedirectResponse
    {
        $topic = $service->createTopic($request->validated(), $request->user());

        return redirect()->route('interactions.discussions.show', $topic)->with('success', 'Topik diskusi dibuat.');
    }

    public function show(DiscussionTopic $discussionTopic): View
    {
        $this->authorize('view', $discussionTopic);
        $discussionTopic->load(['author:id,name,profile_photo_path', 'schoolClass:id,name', 'learningSession:id,title', 'posts' => fn ($q) => $q->whereNull('parent_id')->with(['author:id,name,profile_photo_path', 'reports', 'replies.author:id,name,profile_photo_path', 'replies.reports'])->oldest()]);

        return view('interactions.discussions.show', ['topic' => $discussionTopic]);
    }

    public function post(DiscussionPostRequest $request, DiscussionTopic $discussionTopic, DiscussionService $service): RedirectResponse
    {
        $service->post($discussionTopic, $request->validated(), $request->user());

        return back()->with('success', 'Pesan dikirim.');
    }

    public function report(Request $request, DiscussionPost $discussionPost, DiscussionService $service): RedirectResponse
    {
        $this->authorize('report', $discussionPost);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $service->report($discussionPost, $data['reason'], $request->user());

        return back()->with('success', 'Laporan diteruskan ke pembina.');
    }
}
