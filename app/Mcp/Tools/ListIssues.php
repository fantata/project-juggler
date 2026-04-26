<?php

namespace App\Mcp\Tools;

use App\Services\IssueService;
use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List issues for a project with optional status filter.')]
class ListIssues extends Tool
{
    public function __construct(
        private readonly ProjectService $projects,
        private readonly IssueService $issues,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Project ID'),
            'project_name' => $schema->string()->description('Project name (fuzzy match)'),
            'status' => $schema->string()->description('Filter: open, in_progress, done'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['status' => 'nullable|in:open,in_progress,done']);

        $project = $this->projects->find($request->integer('project_id') ?: null, $request->get('project_name'));

        if (! $project) {
            return Response::error('Project not found');
        }

        $issues = $this->issues->listForProject($project, $request->get('status'));

        return Response::json([
            'project' => $project->name,
            'count' => $issues->count(),
            'issues' => $issues->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'description' => $i->description,
                'status' => $i->status,
                'urgency' => $i->urgency,
                'github_issue_number' => $i->github_issue_number,
                'created_at' => $i->created_at,
            ])->all(),
        ]);
    }
}
