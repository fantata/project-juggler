<?php

namespace App\Mcp\Tools;

use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Retrieve the stored AI context for a project. Claude Code instances should call this at session start to load prior state. Returns the raw context string previously saved by update_project_context.')]
class GetProjectContext extends Tool
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

        return Response::json($this->projects->getContext($project));
    }
}
