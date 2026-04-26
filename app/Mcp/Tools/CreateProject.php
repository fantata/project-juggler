<?php

namespace App\Mcp\Tools;

use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new project. Both name and type are required.')]
class CreateProject extends Tool
{
    public function __construct(private readonly ProjectService $projects) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('Project name'),
            'type' => $schema->string()->required()->description('Project type: client, personal, speculative'),
            'status' => $schema->string()->description('Project status: active (default), paused, blocked, complete, killed'),
            'waiting_on_client' => $schema->boolean()->description('Waiting on client response'),
            'priority' => $schema->integer()->description('Priority (lower = higher priority)'),
            'money_status' => $schema->string()->description('Money status: paid, partial, awaiting, none, speculative'),
            'money_value' => $schema->number()->description('Money value in GBP'),
            'deadline' => $schema->string()->description('Deadline (YYYY-MM-DD)'),
            'next_action' => $schema->string()->description('GTD-style next action'),
            'notes' => $schema->string()->description('Project notes'),
            'github_repo' => $schema->string()->description('GitHub repository (org/repo format)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:client,personal,speculative',
            'status' => 'nullable|in:active,paused,blocked,complete,killed',
            'waiting_on_client' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:1',
            'money_status' => 'nullable|in:paid,partial,awaiting,none,speculative',
            'money_value' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'next_action' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'github_repo' => 'nullable|string|max:255',
        ]);

        $project = $this->projects->create($validated);

        return Response::json([
            'success' => true,
            'message' => "Project '{$project->name}' created",
            'id' => $project->id,
        ]);
    }
}
