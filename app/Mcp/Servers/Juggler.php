<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddDailyNote;
use App\Mcp\Tools\CreateCalendarEvent;
use App\Mcp\Tools\CreateIssue;
use App\Mcp\Tools\CreateProject;
use App\Mcp\Tools\GetDailyNotes;
use App\Mcp\Tools\GetGithubActivity;
use App\Mcp\Tools\GetProject;
use App\Mcp\Tools\GetProjectContext;
use App\Mcp\Tools\GetToday;
use App\Mcp\Tools\ListCalendarEvents;
use App\Mcp\Tools\ListIssues;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\ListTasks;
use App\Mcp\Tools\LogWork;
use App\Mcp\Tools\QuickStatus;
use App\Mcp\Tools\SyncIssues;
use App\Mcp\Tools\UpdateIssue;
use App\Mcp\Tools\UpdateProject;
use App\Mcp\Tools\UpdateProjectContext;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('project-juggler')]
#[Version('2.0.0')]
#[Instructions(<<<'TXT'
Project Juggler is Chris Read's personal project, issue, and task tracker — also
the spine for daily notes, calendar events, and AI-context-per-project.

Use these tools to:
- Orient at session start: `quick_status`, `get_today`, `get_daily_notes`,
  `get_project_context` (for the project you're about to work on).
- Manage projects, issues, tasks: `list_projects`, `get_project`,
  `create_project`, `update_project`, `create_issue`, `update_issue`,
  `list_issues`, `list_tasks`, `sync_issues`.
- Log work and notes: `log_work`, `add_daily_note`.
- Persist session state: `update_project_context` (call at session end).
- Calendar: `list_calendar_events`, `create_calendar_event`.
- GitHub: `get_github_activity`.
TXT)]
class Juggler extends Server
{
    protected array $tools = [
        ListProjects::class,
        GetProject::class,
        CreateProject::class,
        UpdateProject::class,
        LogWork::class,
        QuickStatus::class,
        CreateIssue::class,
        ListIssues::class,
        UpdateIssue::class,
        SyncIssues::class,
        ListTasks::class,
        UpdateProjectContext::class,
        GetProjectContext::class,
        AddDailyNote::class,
        GetDailyNotes::class,
        GetToday::class,
        ListCalendarEvents::class,
        CreateCalendarEvent::class,
        GetGithubActivity::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
