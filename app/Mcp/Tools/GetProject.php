<?php

namespace App\Mcp\Tools;

use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get detailed information about a project by ID or name (fuzzy match). Returns the project plus recent logs, open issues, and (if a github_repo is set) recent commits.')]
class GetProject extends Tool
{
    public function __construct(private readonly ProjectService $projects) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Project ID'),
            'name' => $schema->string()->description('Project name (fuzzy match)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $project = $this->projects->find($request->integer('id') ?: null, $request->get('name'));

        if (! $project) {
            return Response::error('Project not found');
        }

        $project->load([
            'issues' => fn ($q) => $q->whereIn('status', ['open', 'in_progress'])->latest()->limit(10),
            'logs' => fn ($q) => $q->latest()->limit(10),
        ]);

        $project->loadCount(['issues as open_issue_count' => fn ($q) => $q->whereIn('status', ['open', 'in_progress'])]);

        return Response::json([
            'id' => $project->id,
            'name' => $project->name,
            'type' => $project->type,
            'status' => $project->status,
            'priority' => $project->priority,
            'money_status' => $project->money_status,
            'money_value' => $project->money_value,
            'deadline' => $project->deadline?->toDateString(),
            'next_action' => $project->next_action,
            'notes' => $project->notes,
            'github_repo' => $project->github_repo,
            'ai_context' => $project->ai_context,
            'ai_context_updated_at' => $project->ai_context_updated_at,
            'open_issue_count' => $project->open_issue_count,
            'recent_logs' => $project->logs->map(fn ($l) => ['entry' => $l->entry, 'created_at' => $l->created_at])->all(),
            'open_issues' => $project->issues->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'status' => $i->status,
                'urgency' => $i->urgency,
                'github_issue_number' => $i->github_issue_number,
                'created_at' => $i->created_at,
            ])->all(),
        ]);
    }
}
