<?php

namespace App\Mcp\Tools;

use App\Services\ProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Quick overview: active count, blocked projects, projects awaiting money, open issues. Call at session start to orient.')]
class QuickStatus extends Tool
{
    public function __construct(private readonly ProjectService $projects) {}

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        return Response::json($this->projects->quickStatus());
    }
}
