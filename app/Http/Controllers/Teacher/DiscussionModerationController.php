<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\DiscussionPost;
use App\Models\DiscussionTopic;
use App\Services\DiscussionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscussionModerationController extends Controller
{
    public function topic(Request $request, DiscussionTopic $discussionTopic, DiscussionService $service): RedirectResponse
    {
        $this->authorize('moderate', $discussionTopic);
        $data = $request->validate(['action' => ['required', Rule::in(['pin', 'close', 'hide'])]]);
        $service->toggleTopic($discussionTopic, $data['action'], $request->user());

        return back()->with('success', 'Status topik diperbarui.');
    }

    public function post(DiscussionPost $discussionPost, DiscussionService $service): RedirectResponse
    {
        $this->authorize('moderate', $discussionPost);
        $service->moderatePost($discussionPost, request()->user());

        return back()->with('success', 'Status pesan diperbarui.');
    }
}
