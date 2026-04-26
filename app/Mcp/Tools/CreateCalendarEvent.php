<?php

namespace App\Mcp\Tools;

use App\Services\CalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a native calendar event. Title and starts_at are required.')]
class CreateCalendarEvent extends Tool
{
    public function __construct(private readonly CalendarService $calendar) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required()->description('Event title'),
            'starts_at' => $schema->string()->required()->description('ISO datetime e.g. 2026-04-02T10:00:00'),
            'ends_at' => $schema->string()->description('ISO datetime (optional)'),
            'is_all_day' => $schema->boolean()->description('All-day event (default false)'),
            'location' => $schema->string(),
            'description' => $schema->string(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date',
            'is_all_day' => 'nullable|boolean',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $event = $this->calendar->create(array_merge(
            ['is_all_day' => false],
            array_filter($validated, fn ($v) => $v !== null),
        ));

        return Response::json([
            'success' => true,
            'message' => "Event '{$event->title}' created",
            'id' => $event->id,
        ]);
    }
}
