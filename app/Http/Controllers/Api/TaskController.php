<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Issue;
use App\Models\IssueTask;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = IssueTask::with(['issue.project'])
            ->whereHas('issue.project', function ($q) {
                $q->whereNotIn('status', ['complete', 'killed']);
            });

        if (! $request->boolean('include_completed')) {
            $query->where('is_complete', false);
        }

        $tasks = $query->orderBy('position')->get();

        return TaskResource::collection($tasks);
    }

    public function store(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'position' => 'sometimes|integer|min:0',
        ]);

        $position = $validated['position'] ?? ($issue->tasks()->max('position') + 1);

        $task = $issue->tasks()->create([
            'description' => $validated['description'],
            'position' => $position,
            'is_ai_generated' => false,
        ]);

        $issue->project->markTouched();

        return new TaskResource($task->load('issue.project'));
    }

    public function update(Request $request, IssueTask $task)
    {
        $validated = $request->validate([
            'description' => 'sometimes|string|max:500',
            'is_complete' => 'sometimes|boolean',
            'position' => 'sometimes|integer|min:0',
        ]);

        $task->update($validated);
        $task->issue->project->markTouched();

        return new TaskResource($task->fresh()->load('issue.project'));
    }

    public function destroy(IssueTask $task)
    {
        $task->issue->project->markTouched();
        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }
}
