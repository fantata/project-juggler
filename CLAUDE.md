# Project Juggler

## Overview
TALL stack (Tailwind, Alpine, Laravel 12, Livewire 4) app for universal project management.
Manages consultancy projects, podcasts, creative projects, events, and calendar scheduling.
Runs on CapRover with MySQL. Has an MCP server (Node.js, direct MySQL) and syncs with GitHub.

## Architecture
- **Laravel app**: Main web UI with Livewire components
- **CalDAV server**: Embedded sabre/dav for calendar sync (iOS, Android, Thunderbird)
- **MCP server**: `/mcp-server/index.js` — direct MySQL access for Claude integration
- **Skills**: `/skills/` — domain knowledge files for Claude

## Key Paths
- Models: `app/Models/`
- Livewire components: `app/Livewire/`
- Views: `resources/views/livewire/`
- Enums: `app/Enums/`
- Services: `app/Services/`
- Config: `config/project-categories.php`, `config/calendars.php`
- MCP server: `mcp-server/index.js`

## Skills
When working with Project Juggler, read the relevant skill files in `/skills/`
before taking action. Always read `PROJECT-JUGGLER.md` first for system context.
Read domain-specific skills when the task involves that domain.

## MCP Server
The MCP server at `/mcp-server/index.js` provides direct database access.
Use MCP tools for all data operations — don't suggest raw SQL.

## Development
- `composer dev` — runs Laravel server, queue, logs, and Vite
- `php artisan test` — run tests
- Deploy via CapRover (Dockerfile in repo root)

## CalDAV
- Endpoint: `/dav/`
- Auth: HTTP Basic using Laravel user credentials (email + password)
- Clients connect to `https://domain.com/dav/calendars/{email}/default/`

## Project Categories
Defined in `config/project-categories.php`:
- consultancy, podcast, creative, event, generic
- Each category has custom meta fields, vocabulary, and UI behaviour
