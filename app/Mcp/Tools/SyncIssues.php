<?php

namespace App\Mcp\Tools;

use App\Services\IssueService;
use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Sync issues with GitHub for a project that has a github_repo configured. Pulls new and updated issues from GitHub.')]
class SyncIssues extends Tool
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
        ];
    }

    public function handle(Request $request): Response
    {
        $project = $this->projects->find($request->integer('project_id') ?: null, $request->get('project_name'));

        if (! $project) {
            return Response::error('Project not found');
        }

        try {
            $result = $this->issues->syncFromGithub($project);
        } catch (\RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(array_merge(
            ['success' => true, 'message' => "Synced issues for '{$project->name}': {$result['created']} created, {$result['updated']} updated"],
            $result,
        ));
    }
}
