<?php

namespace App\Mcp\Tools;

use App\Services\IssueService;
use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create an issue/ticket on a project. Optionally include sub-task descriptions. If the project has a github_repo configured, the issue is also pushed to GitHub.')]
class CreateIssue extends Tool
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
            'title' => $schema->string()->required()->description('Issue title'),
            'description' => $schema->string()->description('Issue description / action items'),
            'urgency' => $schema->string()->description('low, medium (default), high'),
            'tasks' => $schema->array()->description('Array of sub-task descriptions to create on the issue'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'urgency' => 'nullable|in:low,medium,high',
            'tasks' => 'nullable|array',
            'tasks.*' => 'string',
        ]);

        $project = $this->projects->find($request->integer('project_id') ?: null, $request->get('project_name'));

        if (! $project) {
            return Response::error('Project not found');
        }

        $issue = $this->issues->create(
            $project,
            array_filter([
                'title' => $request->get('title'),
                'description' => $request->get('description'),
                'urgency' => $request->get('urgency'),
            ], fn ($v) => $v !== null),
            (array) $request->get('tasks', []),
        );

        return Response::json([
            'success' => true,
            'message' => "Issue created on '{$project->name}'" . ($issue->github_issue_number ? " (GitHub #{$issue->github_issue_number})" : ''),
            'issue_id' => $issue->id,
            'title' => $issue->title,
            'urgency' => $issue->urgency,
            'github_issue_number' => $issue->github_issue_number,
        ]);
    }
}
