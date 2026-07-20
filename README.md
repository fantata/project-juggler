# Project Juggler

Personal project, task and calendar management system. Second brain and AI PA backbone for Chris Read.

## Stack

- Laravel 12, Livewire 4, Alpine.js, Tailwind CSS (TALL)
- MySQL (shared DigitalOcean managed instance)
- Local dev: <http://project-juggler.test> (Laravel Herd)
- Production: <https://juggler.fantata.dev> (sv3/Hetzner, Docker behind shared Caddy)

## What it does

- Tracks projects (client, personal, speculative), with money status, deadlines, retainers, GitHub repo links
- Tracks issues and sub-tasks against projects, optionally synced with GitHub
- Freeform work logs and personal daily notes
- Native calendar events plus subscribed external ICS feeds with rule-based filtering
- Exports its own native events as an ICS feed for Apple/Google Calendar
- Twice-daily AI briefing emails (8am and 5pm) summarising state via the Anthropic API
- Per-project AI context column for session-to-session memory handoff

## Three front doors

Same domain code, three surfaces:

- **Web UI**: Livewire pages for humans
- **REST API**: Sanctum-authenticated, see [docs/api-reference.md](docs/api-reference.md)
- **MCP server**: Laravel-native, lives in `app/Mcp/`, used by Claude Desktop

## MCP server

Laravel-native MCP implementation in `app/Mcp/`. Tools include `quick-status`, `list-projects`, `get-project`, `create-project`, `update-project`, `log-work`, `create-issue`, `list-issues`, `update-issue`, `sync-issues`, `list-tasks`, `update-project-context`, `get-project-context`, `add-daily-note`, `get-daily-notes`, `get-today`, `list-calendar-events`, `create-calendar-event`, `get-github-activity`.

Auth: same Sanctum tokens as the REST API. Generate from Profile > API Token in the web UI.

The legacy Node MCP server (`mcp-server/`) is deprecated and pending deletion.

## Calendar export (ICS)

Public route: `GET /ics/{token}.ics` (note: web route, NOT under `/api`).

Generate the token from Profile > Calendar Feed in the web UI, then subscribe in Apple Calendar / Google Calendar / Outlook.

Production URL pattern: `https://juggler.fantata.dev/ics/{token}.ics`

Only native `CalendarEvent` rows are exported. Subscribed feed events are deliberately excluded so external calendars don't double up.

## Start of every Claude Code session

1. `quick_status` for current project overview
2. `get_today` for calendar context
3. `get_daily_notes` (last 7 days) if returning after a gap
4. `get_github_activity` (days: 7) for recent commit activity

## End of every Claude Code session

Call `update_project_context` to persist state for future sessions.

## Deployment

Push to `main` -> GitHub Actions -> rsync to sv3 -> `docker compose -f docker-compose.sv3.yml up -d --build`. App runs in Docker on sv3 (138.201.33.178, Hetzner), joined to the shared Caddy `web` network (no published host port), using the shared MySQL.

GitHub Actions requires the `SV3_SSH_KEY` secret (private key for `chris@138.201.33.178`).

The deploy auto-runs `php artisan migrate --force` (plus `config:clear` / `view:clear`), so migrations apply on every deploy. To run them by hand:

```bash
ssh sv3 "cd /home/chris/apps/project-juggler && docker exec project-juggler php artisan migrate --force"
```

## Roadmap

See [SPEC_2026_UPGRADES.md](SPEC_2026_UPGRADES.md) for the full 2026 spec covering memories, agents, domain events, hybrid search, policy files, malleable views and unified search across all data sources.

## Known quirks

- Web detail route is named `projects.detail` (not `projects.show`) to avoid collision with the `apiResource('projects')` route which generates `projects.show`. `start.sh` runs `route:clear` before `route:cache` to prevent stale cache issues.

## License

Private. Not for redistribution.
