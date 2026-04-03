<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\IssueTask;
use App\Models\Project;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $query = $project->issues()->with('tasks');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return IssueResource::collection($query->orderByDesc('created_at')->get());
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:open,in_progress,done',
            'urgency' => 'sometimes|in:low,medium,high',
        ]);

        $issue = $project->issues()->create(array_merge(
            ['status' => 'open', 'urgency' => 'medium'],
            $validated
        ));

        $project->markTouched();

        return new IssueResource($issue->load('tasks'));
    }

    public function update(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:open,in_progress,done',
            'urgency' => 'sometimes|in:low,medium,high',
        ]);

        $issue->update($validated);
        $issue->project->markTouched();

        return new IssueResource($issue->fresh()->load('tasks'));
    }

    public function destroy(Issue $issue)
    {
        $issue->project->markTouched();
        $issue->delete();

        return response()->json(['message' => 'Issue deleted']);
    }
}
