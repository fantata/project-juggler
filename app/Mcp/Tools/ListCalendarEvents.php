<?php

namespace App\Mcp\Tools;

use App\Services\CalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List calendar events (native + external feed events) in a date range. Defaults to today through 7 days from today.')]
class ListCalendarEvents extends Tool
{
    public function __construct(private readonly CalendarService $calendar) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->description('Start date YYYY-MM-DD (default: today)'),
            'to' => $schema->string()->description('End date YYYY-MM-DD (default: 7 days from today)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = $request->get('from') ?: now()->toDateString();
        $to = $request->get('to') ?: now()->addDays(7)->toDateString();

        $events = $this->calendar->inRange($from, $to);

        return Response::json([
            'from' => $from,
            'to' => $to,
            'count' => count($events),
            'events' => $events,
        ]);
    }
}
