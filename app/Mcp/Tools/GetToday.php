<?php

namespace App\Mcp\Tools;

use App\Services\CalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Today's date, calendar events, and a quick task count. Use at session start to orient.")]
class GetToday extends Tool
{
    public function __construct(private readonly CalendarService $calendar) {}

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        return Response::json($this->calendar->today());
    }
}
