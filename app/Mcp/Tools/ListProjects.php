<?php

namespace App\Mcp\Tools;

use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List projects with optional filters. By default returns active, paused, and blocked projects (not complete/killed). Use the status filter to see other statuses.')]
class ListProjects extends Tool
{
    public function __construct(private readonly ProjectService $projects) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Filter by type: client, personal, speculative'),
            'status' => $schema->string()->description('Filter by status: active, paused, blocked, complete, killed'),
            'money_status' => $schema->string()->description('Filter by money status: paid, partial, awaiting, none, speculative'),
            'waiting_on_client' => $schema->boolean()->description('Filter by waiting-on-client status'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'type' => 'nullable|in:client,personal,speculative',
            'status' => 'nullable|in:active,paused,blocked,complete,killed',
            'money_status' => 'nullable|in:paid,partial,awaiting,none,speculative',
            'waiting_on_client' => 'nullable|boolean',
        ]);

        $projects = $this->projects->list(array_filter([
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'money_status' => $request->get('money_status'),
            'waiting_on_client' => $request->has('waiting_on_client') ? $request->boolean('waiting_on_client') : null,
        ], fn ($v) => $v !== null));

        return Response::json([
            'count' => $projects->count(),
            'projects' => $projects->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'status' => $p->status,
                'waiting_on_client' => $p->waiting_on_client,
                'priority' => $p->priority,
                'money_status' => $p->money_status,
                'money_value' => $p->money_value,
                'deadline' => $p->deadline?->toDateString(),
                'next_action' => $p->next_action,
                'github_repo' => $p->github_repo,
                'open_issue_count' => $p->open_issue_count,
                'last_touched' => $p->last_touched_at,
            ])->all(),
        ]);
    }
}
