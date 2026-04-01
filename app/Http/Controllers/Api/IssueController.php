<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\IssueTask;
use App\Models\Project;
use App\Services\EmailParser;
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
        // If raw_email is provided, parse it with AI
        if ($request->filled('raw_email')) {
            $parsed = EmailParser::parse($request->raw_email);

            $issue = $project->issues()->create([
                'title' => $parsed['title'],
                'description' => $parsed['description'],
                'urgency' => $parsed['urgency'],
                'status' => 'open',
                'raw_email' => $request->raw_email,
            ]);

            foreach ($parsed['tasks'] as $i => $taskDesc) {
                $issue->tasks()->create([
                    'description' => $taskDesc,
                    'position' => $i + 1,
                    'is_ai_generated' => true,
                ]);
            }

            $project->markTouched();

            return new IssueResource($issue->load('tasks'));
        }

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
