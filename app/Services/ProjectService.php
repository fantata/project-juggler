<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    public function find(?int $id, ?string $name): ?Project
    {
        if ($id) {
            return Project::find($id);
        }

        if ($name) {
            return Project::where('name', 'like', '%' . $name . '%')->first();
        }

        return null;
    }

    public function list(array $filters = []): Collection
    {
        $query = Project::query()
            ->withCount(['issues as open_issue_count' => fn ($q) => $q->whereIn('status', ['open', 'in_progress'])]);

        foreach (['type', 'status', 'money_status'] as $col) {
            if (! empty($filters[$col])) {
                $query->where($col, $filters[$col]);
            }
        }

        if (array_key_exists('waiting_on_client', $filters)) {
            $query->where('waiting_on_client', (bool) $filters['waiting_on_client']);
        }

        return $query->orderByRaw('CASE WHEN priority IS NULL THEN 1 ELSE 0 END')
            ->orderBy('priority')
            ->orderByRaw("CASE WHEN money_status = 'awaiting' THEN 0 ELSE 1 END")
            ->orderByDesc('money_value')
            ->orderByDesc('last_touched_at')
            ->get();
    }

    public function create(array $data): Project
    {
        return Project::create(array_merge(
            ['status' => 'active', 'money_status' => 'none'],
            $data,
            ['last_touched_at' => now()]
        ));
    }

    public function update(Project $project, array $data): Project
    {
        $project->fill($data);
        $project->last_touched_at = now();
        $project->save();

        return $project->fresh();
    }

    public function quickStatus(): array
    {
        $blocked = Project::where('status', 'blocked')
            ->get(['id', 'name', 'next_action']);

        $awaitingQuery = Project::where('money_status', 'awaiting')
            ->whereNotIn('status', ['complete', 'killed']);

        $awaiting = (clone $awaitingQuery)->get(['id', 'name', 'money_value']);

        return [
            'active_projects' => Project::where('status', 'active')->count(),
            'blocked_projects' => [
                'count' => $blocked->count(),
                'projects' => $blocked,
            ],
            'awaiting_money' => [
                'count' => $awaiting->count(),
                'total_value' => (float) $awaitingQuery->sum('money_value'),
                'projects' => $awaiting,
            ],
            'open_issues' => \App\Models\Issue::whereIn('status', ['open', 'in_progress'])->count(),
        ];
    }

    public function updateContext(Project $project, string $context): int
    {
        $project->ai_context = $context;
        $project->ai_context_updated_at = now();
        $project->last_touched_at = now();
        $project->save();

        return strlen($context);
    }

    public function getContext(Project $project): array
    {
        if (! $project->ai_context) {
            return [
                'project' => $project->name,
                'project_id' => $project->id,
                'context' => null,
                'message' => 'No AI context stored yet',
            ];
        }

        return [
            'project' => $project->name,
            'project_id' => $project->id,
            'context' => $project->ai_context,
            'updated_at' => $project->ai_context_updated_at,
        ];
    }
}
