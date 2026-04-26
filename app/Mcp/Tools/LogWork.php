<?php

namespace App\Mcp\Tools;

use App\Services\LogService;
use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a log entry to a project. Provide project_id or project_name for lookup. Hours is optional.')]
class LogWork extends Tool
{
    public function __construct(
        private readonly ProjectService $projects,
        private readonly LogService $logs,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Project ID'),
            'project_name' => $schema->string()->description('Project name (fuzzy match)'),
            'entry' => $schema->string()->required()->description('Log entry text'),
            'hours' => $schema->number()->description('Hours spent (optional)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'entry' => 'required|string',
            'hours' => 'nullable|numeric|min:0',
        ]);

        $project = $this->projects->find($request->integer('project_id') ?: null, $request->get('project_name'));

        if (! $project) {
            return Response::error('Project not found');
        }

        $hours = $request->has('hours') ? (float) $request->get('hours') : null;
        $this->logs->log($project, $request->get('entry'), $hours);

        return Response::json([
            'success' => true,
            'message' => "Logged work on '{$project->name}'" . ($hours !== null ? " ({$hours}h)" : ''),
            'project_id' => $project->id,
        ]);
    }
}
