<?php

namespace App\Mcp\Tools;

use App\Services\DailyNoteService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get recent personal daily notes. Use at session start to understand context from recent days — especially after a gap.')]
class GetDailyNotes extends Tool
{
    public function __construct(private readonly DailyNoteService $notes) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->description('How many days back to fetch (default 14, max 365)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $days = $request->integer('days') ?: 14;
        $notes = $this->notes->recent($days);

        return Response::json([
            'count' => $notes->count(),
            'days' => $days,
            'notes' => $notes,
        ]);
    }
}
