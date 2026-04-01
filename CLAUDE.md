# Project Juggler

Personal project/task management system and AI PA backbone.

## Stack
- Laravel 12 + Livewire 4 + Alpine.js + Tailwind CSS (TALL)
- Local: http://project-juggler.test (Laravel Herd)
- Production: https://juggler.fantata.dev
- MySQL (shared DO instance)

## Key models
| Model | Purpose |
|-------|---------|
| Project | Top-level work item — client, personal, or speculative |
| ProjectLog | Freeform work diary attached to a project |
| Issue | Bug/feature/task attached to a project |
| IssueTask | Sub-task checklist item on an Issue |
| CalendarEvent | Native calendar event (exported via ICS) |
| IcsFeed | External calendar subscription |
| IcsFeedEvent | Event synced from an external ICS feed |
| DailyNote | Personal daily log — mood, energy, context (not project-specific) |

## MCP server
Location: `mcp-server/index.js`
Connects directly to MySQL (bypasses Laravel).
Tools: list_projects, get_project, create_project, update_project, log_work,
       quick_status, create_issue, list_issues, update_issue, sync_issues,
       list_tasks, update_project_context, get_project_context,
       add_daily_note, get_daily_notes, get_today,
       list_calendar_events, create_calendar_event

Restart: kill the process and let Claude Desktop restart it, or:
  `cd mcp-server && node index.js`

## API
REST API documented in `docs/api-reference.md`.
Auth: Bearer token (generate from Profile > API Token in web UI).
Base URL (prod): https://juggler.fantata.dev/api

## ICS feed
Export URL: `GET /api/ics/{token}.ics` — subscribe this in Apple Calendar.
Token generated from Profile > Calendar Feed in web UI.

## Start of every Claude Code session
1. Run `quick_status` via MCP to get current project overview
2. Check `get_today` for calendar context
3. Check `get_daily_notes` (last 7 days) if returning after a gap

## Deployment
CapRover via GitHub Actions. App name: `project-juggler`.
After changes: commit, push, CapRover deploys automatically.
Run migrations on prod: `ssh sv1 "docker exec <container> php artisan migrate"`

## Uncommitted work as of 2026-04-01
Calendar system, ICS feeds, REST API, UI overhaul, daily notes — all need committing and deploying.
