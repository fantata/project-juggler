<?php

namespace App\Mcp\Tools;

use App\Services\TaskService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List all actionable work items across active projects: standalone issues (issues with no sub-tasks) plus child tasks from issues that have them. Use for a cross-project view of everything that needs doing.')]
class ListTasks extends Tool
{
    public function __construct(private readonly TaskService $tasks) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'include_completed' => $schema->boolean()->description('Include completed items (default false)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $items = $this->tasks->listAll($request->boolean('include_completed'));

        return Response::json([
            'count' => count($items),
            'items' => $items,
        ]);
    }
}
