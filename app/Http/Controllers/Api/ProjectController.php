<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()
            ->withCount(['issues as open_issue_count' => function ($q) {
                $q->whereIn('status', ['open', 'in_progress']);
            }]);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('money_status')) {
            $query->where('money_status', $request->money_status);
        }

        if ($request->boolean('waiting_on_client')) {
            $query->where('waiting_on_client', true);
        }

        if ($request->boolean('is_retainer')) {
            $query->where('is_retainer', true);
        }

        // Default: exclude complete/killed unless requested
        if (! $request->has('status') && ! $request->boolean('include_completed')) {
            $query->whereNotIn('status', ['complete', 'killed']);
        }

        $query->orderByRaw('CASE WHEN priority IS NULL THEN 1 ELSE 0 END')
            ->orderBy('priority', 'asc')
            ->orderBy('deadline', 'asc');

        return ProjectResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:client,personal,speculative',
            'status' => 'sometimes|in:active,paused,blocked,complete,killed',
            'money_status' => 'sometimes|in:paid,partial,awaiting,none,speculative',
            'money_value' => 'sometimes|nullable|numeric|min:0',
            'deadline' => 'sometimes|nullable|date',
            'next_action' => 'sometimes|nullable|string|max:255',
            'notes' => 'sometimes|nullable|string',
            'github_repo' => 'sometimes|nullable|string|max:255',
            'priority' => 'sometimes|nullable|integer|min:1',
            'waiting_on_client' => 'sometimes|boolean',
            'is_retainer' => 'sometimes|boolean',
            'retainer_frequency' => 'sometimes|nullable|in:monthly,yearly',
            'retainer_amount' => 'sometimes|nullable|numeric|min:0',
        ]);

        $project = Project::create(array_merge(
            ['status' => 'active', 'money_status' => 'none'],
            $validated,
            ['last_touched_at' => now()]
        ));

        return new ProjectResource($project);
    }

    public function show(Project $project)
    {
        $project->load([
            'issues' => fn ($q) => $q->whereIn('status', ['open', 'in_progress'])->with('tasks'),
            'logs' => fn ($q) => $q->latest()->limit(10),
        ]);

        $project->loadCount(['issues as open_issue_count' => function ($q) {
            $q->whereIn('status', ['open', 'in_progress']);
        }]);

        return new ProjectResource($project);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:client,personal,speculative',
            'status' => 'sometimes|in:active,paused,blocked,complete,killed',
            'money_status' => 'sometimes|in:paid,partial,awaiting,none,speculative',
            'money_value' => 'sometimes|nullable|numeric|min:0',
            'deadline' => 'sometimes|nullable|date',
            'next_action' => 'sometimes|nullable|string|max:255',
            'notes' => 'sometimes|nullable|string',
            'github_repo' => 'sometimes|nullable|string|max:255',
            'priority' => 'sometimes|nullable|integer|min:1',
            'waiting_on_client' => 'sometimes|boolean',
            'is_retainer' => 'sometimes|boolean',
            'retainer_frequency' => 'sometimes|nullable|in:monthly,yearly',
            'retainer_amount' => 'sometimes|nullable|numeric|min:0',
        ]);

        $project->update($validated);
        $project->markTouched();

        return new ProjectResource($project->fresh());
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }
}
