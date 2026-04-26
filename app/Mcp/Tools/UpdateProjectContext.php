<?php

namespace App\Mcp\Tools;

use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description(<<<'TXT'
Store AI-generated context for a project. Claude Code instances should call this at the END of a working session to persist state for future sessions.

Accepts markdown or JSON string. Recommended JSON structure:
{
  "summary": "One-sentence current state of the project",
  "stack": ["key", "technologies", "in use"],
  "key_files": ["list of important file paths with brief note"],
  "decisions": ["Architectural or design decisions made, with rationale"],
  "current_focus": "What is actively being worked on right now",
  "blockers": ["Anything blocking progress"],
  "next_steps": ["Ordered list of what to do next"],
  "open_questions": ["Unresolved questions or unknowns"],
  "notes": "Any other freeform context useful for a future Claude session"
}

Overwrites previous context. Max ~100KB. The stored context is returned by get_project and get_project_context.
TXT)]
class UpdateProjectContext extends Tool
{
    public function __construct(private readonly ProjectService $projects) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Project ID'),
            'name' => $schema->string()->description('Project name (fuzzy match)'),
            'context' => $schema->string()->required()->description('Context blob — markdown or JSON string'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'context' => 'required|string|max:102400',
        ]);

        $project = $this->projects->find($request->integer('id') ?: null, $request->get('name'));

        if (! $project) {
            return Response::error('Project not found');
        }

        $bytes = $this->projects->updateContext($project, $request->get('context'));

        return Response::json([
            'success' => true,
            'message' => "AI context saved for '{$project->name}'",
            'project_id' => $project->id,
            'bytes' => $bytes,
        ]);
    }
}
