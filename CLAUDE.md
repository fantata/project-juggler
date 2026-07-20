# Project Juggler

Personal project/task management system and AI PA backbone.

## Stack

- Laravel 12 + Livewire 4 + Alpine.js + Tailwind CSS (TALL)
- Local: <http://project-juggler.test> (Laravel Herd)
- Production: <https://project-juggler.sv3.fantata.com> (sv3/Hetzner, Docker behind shared Caddy)
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

Location: `mcp-server/index.js`Connects directly to MySQL (bypasses Laravel). Tools: list_projects, get_project, create_project, update_project, log_work, quick_status, create_issue, list_issues, update_issue, sync_issues, list_tasks, update_project_context, get_project_context, add_daily_note, get_daily_notes, get_today, list_calendar_events, create_calendar_event, get_github_activity

Restart: kill the process and let Claude Desktop restart it, or: `cd mcp-server && node index.js`

## API

REST API documented in `docs/api-reference.md`. Auth: Bearer token (generate from Profile > API Token in web UI). Base URL (prod): <https://project-juggler.sv3.fantata.com/api>

## ICS feed

Export URL: `GET /api/ics/{token}.ics` — subscribe this in Apple Calendar. Token generated from Profile > Calendar Feed in web UI.

## Start of every Claude Code session

1. Run `quick_status` via MCP to get current project overview
2. Check `get_today` for calendar context
3. Check `get_daily_notes` (last 7 days) if returning after a gap
4. Run `get_github_activity` (days: 7) to see recent commit activity

## End of every Claude Code session

- Call `update_project_context` to persist state for future sessions

## Deployment

Push to main → GitHub Actions → rsync to sv3 → `docker compose -f docker-compose.sv3.yml up -d --build`. App runs in Docker on sv3 (138.201.33.178, Hetzner), joined to the shared Caddy `web` network (no published host port), using the shared MySQL.

GitHub Actions requires the `SV3_SSH_KEY` secret (private key for `chris@138.201.33.178`).

The deploy auto-runs migrations (`php artisan migrate --force`), so schema changes apply on deploy. To run them by hand: `ssh sv3 "cd /home/chris/apps/project-juggler && docker exec project-juggler php artisan migrate --force"`

## Known issues

- Web route is `projects.detail` (not `projects.show`) to avoid collision with the API `apiResource('projects')` which generates `projects.show`. `start.sh` runs `route:clear` before `route:cache` to prevent stale cache issues.
