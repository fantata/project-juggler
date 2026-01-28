<?php

namespace App\Console\Commands;

use App\Enums\MoneyStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Issue;
use App\Models\Project;
use App\Services\EmailParser;
use App\Services\GitHubService;
use Illuminate\Console\Command;

class McpServer extends Command
{
    protected $signature = 'mcp:serve';
    protected $description = 'Run the MCP server for Project Juggler';

    private array $tools = [
        [
            'name' => 'list_projects',
            'description' => 'List all projects with optional filters',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'description' => 'Filter by project type: client, personal, speculative',
                        'enum' => ['client', 'personal', 'speculative'],
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Filter by status: active, paused, blocked, complete, killed',
                        'enum' => ['active', 'paused', 'blocked', 'complete', 'killed'],
                    ],
                    'money_status' => [
                        'type' => 'string',
                        'description' => 'Filter by money status: paid, partial, awaiting, none, speculative',
                        'enum' => ['paid', 'partial', 'awaiting', 'none', 'speculative'],
                    ],
                ],
            ],
        ],
        [
            'name' => 'get_project',
            'description' => 'Get detailed information about a project by ID or name (fuzzy match)',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Project ID',
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Project name (fuzzy match)',
                    ],
                ],
            ],
        ],
        [
            'name' => 'create_project',
            'description' => 'Create a new project',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'Project name',
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'Project type: client, personal, speculative',
                        'enum' => ['client', 'personal', 'speculative'],
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Project status: active, paused, blocked, complete, killed',
                        'enum' => ['active', 'paused', 'blocked', 'complete', 'killed'],
                    ],
                    'money_status' => [
                        'type' => 'string',
                        'description' => 'Money status: paid, partial, awaiting, none, speculative',
                        'enum' => ['paid', 'partial', 'awaiting', 'none', 'speculative'],
                    ],
                    'money_value' => [
                        'type' => 'number',
                        'description' => 'Money value in GBP',
                    ],
                    'deadline' => [
                        'type' => 'string',
                        'description' => 'Deadline date (YYYY-MM-DD format)',
                    ],
                    'next_action' => [
                        'type' => 'string',
                        'description' => 'GTD-style next action',
                    ],
                    'notes' => [
                        'type' => 'string',
                        'description' => 'Project notes',
                    ],
                    'github_repo' => [
                        'type' => 'string',
                        'description' => 'GitHub repository (org/repo format)',
                    ],
                ],
                'required' => ['name', 'type'],
            ],
        ],
        [
            'name' => 'update_project',
            'description' => 'Update an existing project (partial updates allowed)',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Project ID',
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Project name (for lookup if ID not provided)',
                    ],
                    'new_name' => [
                        'type' => 'string',
                        'description' => 'New project name',
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'Project type',
                        'enum' => ['client', 'personal', 'speculative'],
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Project status',
                        'enum' => ['active', 'paused', 'blocked', 'complete', 'killed'],
                    ],
                    'money_status' => [
                        'type' => 'string',
                        'description' => 'Money status',
                        'enum' => ['paid', 'partial', 'awaiting', 'none', 'speculative'],
                    ],
                    'money_value' => [
                        'type' => 'number',
                        'description' => 'Money value in GBP',
                    ],
                    'deadline' => [
                        'type' => 'string',
                        'description' => 'Deadline date (YYYY-MM-DD format, use empty string to clear)',
                    ],
                    'next_action' => [
                        'type' => 'string',
                        'description' => 'GTD-style next action (use empty string to clear)',
                    ],
                    'notes' => [
                        'type' => 'string',
                        'description' => 'Project notes',
                    ],
                    'github_repo' => [
                        'type' => 'string',
                        'description' => 'GitHub repository (org/repo format, use empty string to clear)',
                    ],
                ],
            ],
        ],
        [
            'name' => 'log_work',
            'description' => 'Add a log entry to a project',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'project_id' => [
                        'type' => 'integer',
                        'description' => 'Project ID',
                    ],
                    'project_name' => [
                        'type' => 'string',
                        'description' => 'Project name (fuzzy match, used if project_id not provided)',
                    ],
                    'entry' => [
                        'type' => 'string',
                        'description' => 'Log entry text describing what was done',
                    ],
                ],
                'required' => ['entry'],
            ],
        ],
        [
            'name' => 'quick_status',
            'description' => 'Get a quick overview: active projects count, blocked projects, upcoming deadlines, projects awaiting money, open issues',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [],
            ],
        ],
        [
            'name' => 'create_issue',
            'description' => 'Create an issue/ticket on a project. Either provide a title directly, or provide raw_email to have AI parse it into a structured issue.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'project_id' => [
                        'type' => 'integer',
                        'description' => 'Project ID',
                    ],
                    'project_name' => [
                        'type' => 'string',
                        'description' => 'Project name (fuzzy match, used if project_id not provided)',
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'Issue title (optional if raw_email provided)',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Issue description / action items',
                    ],
                    'urgency' => [
                        'type' => 'string',
                        'description' => 'Issue urgency',
                        'enum' => ['low', 'medium', 'high'],
                    ],
                    'raw_email' => [
                        'type' => 'string',
                        'description' => 'Raw client email text. If provided without a title, AI will parse it to extract title, description, and urgency.',
                    ],
                ],
            ],
        ],
        [
            'name' => 'list_issues',
            'description' => 'List issues for a project with optional status filter',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'project_id' => [
                        'type' => 'integer',
                        'description' => 'Project ID',
                    ],
                    'project_name' => [
                        'type' => 'string',
                        'description' => 'Project name (fuzzy match)',
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Filter by issue status',
                        'enum' => ['open', 'in_progress', 'done'],
                    ],
                ],
            ],
        ],
        [
            'name' => 'update_issue',
            'description' => 'Update an issue status, title, description, or urgency',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Issue ID',
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'New issue title',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'New issue description',
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'New issue status',
                        'enum' => ['open', 'in_progress', 'done'],
                    ],
                    'urgency' => [
                        'type' => 'string',
                        'description' => 'New issue urgency',
                        'enum' => ['low', 'medium', 'high'],
                    ],
                ],
                'required' => ['id'],
            ],
        ],
        [
            'name' => 'sync_issues',
            'description' => 'Sync issues with GitHub for a project that has a github_repo configured. Pulls new/updated issues from GitHub and creates/updates them locally.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'project_id' => [
                        'type' => 'integer',
                        'description' => 'Project ID',
                    ],
                    'project_name' => [
                        'type' => 'string',
                        'description' => 'Project name (fuzzy match)',
                    ],
                ],
            ],
        ],
    ];

    public function handle(): int
    {
        // Disable output buffering for real-time communication
        if (ob_get_level()) {
            ob_end_flush();
        }

        // Set streams to non-blocking mode isn't needed, but ensure they're open
        stream_set_blocking(STDIN, true);
        stream_set_blocking(STDOUT, true);

        // Main loop - read line by line from stdin
        while (!feof(STDIN)) {
            $line = fgets(STDIN);

            if ($line === false) {
                // No data available, small sleep to prevent CPU spin
                usleep(10000); // 10ms
                continue;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $request = json_decode($line, true);
            if (!$request) {
                continue;
            }

            $response = $this->handleRequest($request);
            $this->sendResponse($response);
        }

        return Command::SUCCESS;
    }

    private function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        $id = $request['id'] ?? null;

        return match ($method) {
            'initialize' => $this->handleInitialize($id),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolCall($id, $request['params'] ?? []),
            'notifications/initialized' => null,
            default => $this->errorResponse($id, -32601, "Method not found: {$method}"),
        };
    }

    private function handleInitialize($id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => [],
                ],
                'serverInfo' => [
                    'name' => 'project-juggler',
                    'version' => '1.0.0',
                ],
            ],
        ];
    }

    private function handleToolsList($id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => $this->tools,
            ],
        ];
    }

    private function handleToolCall($id, array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        $result = match ($toolName) {
            'list_projects' => $this->toolListProjects($arguments),
            'get_project' => $this->toolGetProject($arguments),
            'create_project' => $this->toolCreateProject($arguments),
            'update_project' => $this->toolUpdateProject($arguments),
            'log_work' => $this->toolLogWork($arguments),
            'quick_status' => $this->toolQuickStatus(),
            'create_issue' => $this->toolCreateIssue($arguments),
            'list_issues' => $this->toolListIssues($arguments),
            'update_issue' => $this->toolUpdateIssue($arguments),
            'sync_issues' => $this->toolSyncIssues($arguments),
            default => ['error' => "Unknown tool: {$toolName}"],
        };

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT),
                    ],
                ],
            ],
        ];
    }

    private function toolListProjects(array $args): array
    {
        $query = Project::query()
            ->withCount(['issues as open_issue_count' => function ($q) {
                $q->whereIn('status', ['open', 'in_progress']);
            }]);

        if (!empty($args['type'])) {
            $query->where('type', $args['type']);
        }
        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        if (!empty($args['money_status'])) {
            $query->where('money_status', $args['money_status']);
        }

        $projects = $query->orderBy('last_touched_at', 'desc')->get();

        return [
            'count' => $projects->count(),
            'projects' => $projects->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type->value,
                'status' => $p->status->value,
                'money_status' => $p->money_status->value,
                'money_value' => $p->money_value,
                'deadline' => $p->deadline?->format('Y-m-d'),
                'next_action' => $p->next_action,
                'github_repo' => $p->github_repo,
                'open_issue_count' => $p->open_issue_count,
                'last_touched' => $p->last_touched_at?->diffForHumans(),
            ])->toArray(),
        ];
    }

    private function toolGetProject(array $args): array
    {
        $project = $this->findProject($args);

        if (!$project) {
            return ['error' => 'Project not found'];
        }

        $logs = $project->logs()->orderByDesc('created_at')->limit(10)->get();
        $openIssueCount = $project->issues()->whereIn('status', ['open', 'in_progress'])->count();
        $issues = $project->issues()
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'type' => $project->type->value,
            'status' => $project->status->value,
            'money_status' => $project->money_status->value,
            'money_value' => $project->money_value,
            'deadline' => $project->deadline?->format('Y-m-d'),
            'next_action' => $project->next_action,
            'notes' => $project->notes,
            'github_repo' => $project->github_repo,
            'open_issue_count' => $openIssueCount,
            'last_touched_at' => $project->last_touched_at?->format('Y-m-d H:i:s'),
            'created_at' => $project->created_at->format('Y-m-d H:i:s'),
            'recent_logs' => $logs->map(fn($l) => [
                'entry' => $l->entry,
                'created_at' => $l->created_at->format('Y-m-d H:i:s'),
            ])->toArray(),
            'open_issues' => $issues->map(fn($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'status' => $i->status->value,
                'urgency' => $i->urgency->value,
                'github_issue_number' => $i->github_issue_number,
                'created_at' => $i->created_at->format('Y-m-d H:i:s'),
            ])->toArray(),
        ];
    }

    private function toolCreateProject(array $args): array
    {
        if (empty($args['name'])) {
            return ['error' => 'Name is required'];
        }
        if (empty($args['type'])) {
            return ['error' => 'Type is required'];
        }

        $project = Project::create([
            'name' => $args['name'],
            'type' => $args['type'],
            'status' => $args['status'] ?? 'active',
            'money_status' => $args['money_status'] ?? 'none',
            'money_value' => $args['money_value'] ?? null,
            'deadline' => !empty($args['deadline']) ? $args['deadline'] : null,
            'next_action' => $args['next_action'] ?? null,
            'notes' => $args['notes'] ?? null,
            'github_repo' => $args['github_repo'] ?? null,
            'last_touched_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => "Project '{$project->name}' created",
            'id' => $project->id,
        ];
    }

    private function toolUpdateProject(array $args): array
    {
        $project = $this->findProject($args);

        if (!$project) {
            return ['error' => 'Project not found'];
        }

        $updates = [];

        if (isset($args['new_name'])) {
            $updates['name'] = $args['new_name'];
        }
        if (isset($args['type'])) {
            $updates['type'] = $args['type'];
        }
        if (isset($args['status'])) {
            $updates['status'] = $args['status'];
        }
        if (isset($args['money_status'])) {
            $updates['money_status'] = $args['money_status'];
        }
        if (isset($args['money_value'])) {
            $updates['money_value'] = $args['money_value'];
        }
        if (array_key_exists('deadline', $args)) {
            $updates['deadline'] = !empty($args['deadline']) ? $args['deadline'] : null;
        }
        if (array_key_exists('next_action', $args)) {
            $updates['next_action'] = !empty($args['next_action']) ? $args['next_action'] : null;
        }
        if (isset($args['notes'])) {
            $updates['notes'] = $args['notes'];
        }
        if (array_key_exists('github_repo', $args)) {
            $updates['github_repo'] = !empty($args['github_repo']) ? $args['github_repo'] : null;
        }

        $updates['last_touched_at'] = now();

        $project->update($updates);

        return [
            'success' => true,
            'message' => "Project '{$project->name}' updated",
            'id' => $project->id,
        ];
    }

    private function toolLogWork(array $args): array
    {
        if (empty($args['entry'])) {
            return ['error' => 'Entry text is required'];
        }

        $project = $this->findProject([
            'id' => $args['project_id'] ?? null,
            'name' => $args['project_name'] ?? null,
        ]);

        if (!$project) {
            return ['error' => 'Project not found'];
        }

        $project->logs()->create([
            'entry' => $args['entry'],
        ]);

        $project->update(['last_touched_at' => now()]);

        return [
            'success' => true,
            'message' => "Logged work on '{$project->name}'",
            'project_id' => $project->id,
        ];
    }

    private function toolQuickStatus(): array
    {
        $activeCount = Project::where('status', 'active')->count();

        $blockedProjects = Project::where('status', 'blocked')
            ->get(['id', 'name', 'next_action'])
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'next_action' => $p->next_action,
            ])
            ->toArray();

        $upcomingDeadlines = Project::whereNotNull('deadline')
            ->where('deadline', '<=', now()->addDays(7))
            ->where('deadline', '>=', now())
            ->whereNotIn('status', ['complete', 'killed'])
            ->orderBy('deadline')
            ->get(['id', 'name', 'deadline'])
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'deadline' => $p->deadline->format('Y-m-d'),
                'days_left' => $p->deadline->diffInDays(now()),
            ])
            ->toArray();

        $awaitingMoney = Project::where('money_status', 'awaiting')
            ->whereNotIn('status', ['complete', 'killed'])
            ->get(['id', 'name', 'money_value'])
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'money_value' => $p->money_value,
            ])
            ->toArray();

        $totalAwaiting = Project::where('money_status', 'awaiting')
            ->whereNotIn('status', ['complete', 'killed'])
            ->sum('money_value');

        $openIssueCount = Issue::whereIn('status', ['open', 'in_progress'])->count();

        return [
            'active_projects' => $activeCount,
            'blocked_projects' => [
                'count' => count($blockedProjects),
                'projects' => $blockedProjects,
            ],
            'upcoming_deadlines' => [
                'count' => count($upcomingDeadlines),
                'projects' => $upcomingDeadlines,
            ],
            'awaiting_money' => [
                'count' => count($awaitingMoney),
                'total_value' => $totalAwaiting,
                'projects' => $awaitingMoney,
            ],
            'open_issues' => $openIssueCount,
        ];
    }

    private function toolCreateIssue(array $args): array
    {
        $project = $this->findProject([
            'id' => $args['project_id'] ?? null,
            'name' => $args['project_name'] ?? null,
        ]);

        if (!$project) {
            return ['error' => 'Project not found'];
        }

        $title = $args['title'] ?? null;
        $description = $args['description'] ?? null;
        $urgency = $args['urgency'] ?? 'medium';

        if (!empty($args['raw_email']) && empty($title)) {
            $parsed = EmailParser::parse($args['raw_email']);
            $title = $parsed['title'];
            $description = $description ?? $parsed['description'];
            $urgency = $parsed['urgency'];
        }

        if (empty($title)) {
            return ['error' => 'Title is required (or provide raw_email for AI parsing)'];
        }

        $issue = $project->issues()->create([
            'title' => $title,
            'description' => $description,
            'status' => 'open',
            'urgency' => $urgency,
            'raw_email' => $args['raw_email'] ?? null,
        ]);

        // Push to GitHub if configured
        $githubNumber = null;
        if ($project->github_repo && GitHubService::isConfigured()) {
            try {
                $ghIssue = GitHubService::createIssue($project->github_repo, $title, $description);
                if (!empty($ghIssue['number'])) {
                    $issue->update(['github_issue_number' => $ghIssue['number']]);
                    $githubNumber = $ghIssue['number'];
                }
            } catch (\Exception $e) {
                // GitHub push failed — issue still created locally
            }
        }

        $project->update(['last_touched_at' => now()]);

        return [
            'success' => true,
            'message' => "Issue created on '{$project->name}'" . ($githubNumber ? " (GitHub #$githubNumber)" : ''),
            'issue_id' => $issue->id,
            'title' => $issue->title,
            'urgency' => $urgency,
            'github_issue_number' => $githubNumber,
        ];
    }

    private function toolListIssues(array $args): array
    {
        $project = $this->findProject([
            'id' => $args['project_id'] ?? null,
            'name' => $args['project_name'] ?? null,
        ]);

        if (!$project) {
            return ['error' => 'Project not found'];
        }

        $query = $project->issues();

        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }

        $issues = $query->orderByDesc('created_at')->get();

        return [
            'project' => $project->name,
            'count' => $issues->count(),
            'issues' => $issues->map(fn($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'description' => $i->description,
                'status' => $i->status->value,
                'urgency' => $i->urgency->value,
                'github_issue_number' => $i->github_issue_number,
                'created_at' => $i->created_at->format('Y-m-d H:i:s'),
            ])->toArray(),
        ];
    }

    private function toolUpdateIssue(array $args): array
    {
        if (empty($args['id'])) {
            return ['error' => 'Issue ID is required'];
        }

        $issue = Issue::find($args['id']);

        if (!$issue) {
            return ['error' => 'Issue not found'];
        }

        $updates = [];

        if (isset($args['title'])) {
            $updates['title'] = $args['title'];
        }
        if (isset($args['description'])) {
            $updates['description'] = $args['description'];
        }
        if (isset($args['status'])) {
            $updates['status'] = $args['status'];
        }
        if (isset($args['urgency'])) {
            $updates['urgency'] = $args['urgency'];
        }

        $oldStatus = $issue->status->value;
        $issue->update($updates);
        $issue->project->update(['last_touched_at' => now()]);

        // Push status change to GitHub
        if (isset($args['status']) && $issue->github_issue_number && $issue->project->github_repo && GitHubService::isConfigured()) {
            try {
                if ($args['status'] === 'done' && $oldStatus !== 'done') {
                    GitHubService::closeIssue($issue->project->github_repo, $issue->github_issue_number);
                } elseif ($args['status'] !== 'done' && $oldStatus === 'done') {
                    GitHubService::reopenIssue($issue->project->github_repo, $issue->github_issue_number);
                }
            } catch (\Exception $e) {
                // GitHub sync failed silently
            }
        }

        return [
            'success' => true,
            'message' => "Issue '{$issue->title}' updated",
            'id' => $issue->id,
        ];
    }

    private function toolSyncIssues(array $args): array
    {
        $project = $this->findProject([
            'id' => $args['project_id'] ?? null,
            'name' => $args['project_name'] ?? null,
        ]);

        if (!$project) {
            return ['error' => 'Project not found'];
        }

        if (!$project->github_repo) {
            return ['error' => 'Project has no GitHub repo configured'];
        }

        if (!GitHubService::isConfigured()) {
            return ['error' => 'GITHUB_TOKEN not configured'];
        }

        try {
            $ghIssues = GitHubService::listIssues($project->github_repo);
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch GitHub issues: ' . $e->getMessage()];
        }

        $created = 0;
        $updated = 0;

        foreach ($ghIssues as $ghIssue) {
            $number = $ghIssue['number'];
            $existing = $project->issues()
                ->where('github_issue_number', $number)
                ->first();

            $ghStatus = $ghIssue['state'] === 'closed' ? 'done' : 'open';

            if ($existing) {
                $updates = ['title' => $ghIssue['title']];
                if ($ghStatus === 'done' && $existing->status->value !== 'done') {
                    $updates['status'] = 'done';
                    $updated++;
                } elseif ($ghStatus === 'open' && $existing->status->value === 'done') {
                    $updates['status'] = 'open';
                    $updated++;
                }
                if ($existing->title !== $ghIssue['title']) {
                    $updated++;
                }
                $existing->update($updates);
            } else {
                $project->issues()->create([
                    'title' => $ghIssue['title'],
                    'description' => $ghIssue['body'] ?? null,
                    'status' => $ghStatus,
                    'urgency' => 'medium',
                    'github_issue_number' => $number,
                ]);
                $created++;
            }
        }

        return [
            'success' => true,
            'message' => "Synced issues for '{$project->name}': {$created} created, {$updated} updated",
            'created' => $created,
            'updated' => $updated,
            'total_github_issues' => count($ghIssues),
        ];
    }

    private function findProject(array $args): ?Project
    {
        if (!empty($args['id'])) {
            return Project::find($args['id']);
        }

        if (!empty($args['name'])) {
            return Project::where('name', 'LIKE', '%' . $args['name'] . '%')->first();
        }

        return null;
    }

    private function errorResponse($id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    private function sendResponse(?array $response): void
    {
        if ($response === null) {
            return;
        }

        $json = json_encode($response);
        fwrite(STDOUT, $json . "\n");
        fflush(STDOUT);
    }
}
