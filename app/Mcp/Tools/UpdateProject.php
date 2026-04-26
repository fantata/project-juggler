<?php

namespace App\Mcp\Tools;

use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update an existing project (partial updates allowed). Lookup by id or name.')]
class UpdateProject extends Tool
{
    public function __construct(private readonly ProjectService $projects) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Project ID'),
            'name' => $schema->string()->description('Project name (for lookup if ID not provided)'),
            'new_name' => $schema->string()->description('New project name'),
            'type' => $schema->string()->description('client, personal, speculative'),
            'status' => $schema->string()->description('active, paused, blocked, complete, killed'),
            'waiting_on_client' => $schema->boolean(),
            'priority' => $schema->integer()->description('lower = higher priority'),
            'money_status' => $schema->string()->description('paid, partial, awaiting, none, speculative'),
            'money_value' => $schema->number(),
            'deadline' => $schema->string()->description('YYYY-MM-DD or empty to clear'),
            'next_action' => $schema->string()->description('Empty to clear'),
            'notes' => $schema->string(),
            'github_repo' => $schema->string()->description('org/repo, empty to clear'),
        ];
    }

    public function handle(Request $request): Response
    {
        $project = $this->projects->find($request->integer('id') ?: null, $request->get('name'));

        if (! $project) {
            return Response::error('Project not found');
        }

        $request->validate([
            'new_name' => 'nullable|string|max:255',
            'type' => 'nullable|in:client,personal,speculative',
            'status' => 'nullable|in:active,paused,blocked,complete,killed',
            'waiting_on_client' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:1',
            'money_status' => 'nullable|in:paid,partial,awaiting,none,speculative',
            'money_value' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'next_action' => 'nullable|string',
            'notes' => 'nullable|string',
            'github_repo' => 'nullable|string|max:255',
        ]);

        $data = [];
        foreach (['type', 'status', 'waiting_on_client', 'priority', 'money_status', 'money_value', 'deadline', 'next_action', 'notes', 'github_repo'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->get($field);
            }
        }

        if ($request->has('new_name')) {
            $data['name'] = $request->get('new_name');
        }

        $this->projects->update($project, $data);

        return Response::json([
            'success' => true,
            'message' => "Project '{$project->name}' updated",
            'id' => $project->id,
        ]);
    }
}
