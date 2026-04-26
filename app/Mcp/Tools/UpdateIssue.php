<?php

namespace App\Mcp\Tools;

use App\Models\Issue;
use App\Services\IssueService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update an issue (status, title, description, or urgency). Status changes propagate to GitHub if the issue is linked.')]
class UpdateIssue extends Tool
{
    public function __construct(private readonly IssueService $issues) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required()->description('Issue ID'),
            'title' => $schema->string(),
            'description' => $schema->string(),
            'status' => $schema->string()->description('open, in_progress, done'),
            'urgency' => $schema->string()->description('low, medium, high'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'id' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:open,in_progress,done',
            'urgency' => 'nullable|in:low,medium,high',
        ]);

        $issue = Issue::find($request->integer('id'));

        if (! $issue) {
            return Response::error('Issue not found');
        }

        $data = array_filter(
            $request->only(['title', 'description', 'status', 'urgency']),
            fn ($v) => $v !== null,
        );

        $this->issues->update($issue, $data);

        return Response::json([
            'success' => true,
            'message' => "Issue '{$issue->title}' updated",
            'id' => $issue->id,
        ]);
    }
}
