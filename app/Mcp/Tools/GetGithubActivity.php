<?php

namespace App\Mcp\Tools;

use App\Services\GithubActivityService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Recent GitHub commit activity across all repos in the org, grouped by repo and mapped to projects where possible. Use to see where time has been spent or to catch up after a gap. Requires GITHUB_TOKEN with read:org and repo scopes.')]
class GetGithubActivity extends Tool
{
    public function __construct(private readonly GithubActivityService $activity) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->description('How many days back to look (default 14, max 30)'),
            'repo' => $schema->string()->description('Filter to a specific repo slug e.g. "site-audit-master"'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $result = $this->activity->recentCommits(
                $request->integer('days') ?: 14,
                $request->get('repo'),
            );
        } catch (\RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json($result);
    }
}
