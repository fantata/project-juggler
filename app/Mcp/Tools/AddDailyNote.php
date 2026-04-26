<?php

namespace App\Mcp\Tools;

use App\Services\DailyNoteService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a personal daily note — for mood, energy, context about a gap in work, or anything not project-specific. Call when returning after time away, or to capture current state.')]
class AddDailyNote extends Tool
{
    public function __construct(private readonly DailyNoteService $notes) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'body' => $schema->string()->required()->description('The note content'),
            'energy_level' => $schema->string()->description('Current energy level: low, medium, high'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'body' => 'required|string',
            'energy_level' => 'nullable|in:low,medium,high',
        ]);

        $note = $this->notes->create($request->get('body'), $request->get('energy_level'));

        return Response::json([
            'success' => true,
            'message' => 'Daily note saved',
            'id' => $note->id,
        ]);
    }
}
