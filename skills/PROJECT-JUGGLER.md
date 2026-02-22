# Project Juggler — System Knowledge

## What This Is
Project Juggler is Chris Read's universal planning tool. It manages projects,
issues, tasks, and calendar events across multiple domains: client consultancy
(Fantata), improv training (Dogface), podcasts, creative projects, and events.

## Data Model
- **Projects** have a category (consultancy, podcast, creative, event, generic)
  and category-specific meta fields stored as JSON
- **Issues** are the main work items within a project. Their label changes by
  category: "Issue" for consultancy, "Episode" for podcast, "Section" for
  creative, etc.
- **Tasks** are checklist items within issues
- **Calendar Events** are stored via CalDAV (sabre/dav) and can be linked to
  projects/issues via X-PJ-PROJECT-ID custom properties
- **External Calendar Events** are cached from Google Calendar ICS feeds

## MCP Tools Available
- `list_projects` — filter by type, status, money_status, waiting_on_client
- `get_project` — full detail including logs and open issues
- `create_project` / `update_project` — include category and meta fields
- `create_issue` / `update_issue` / `list_issues` — with raw_email AI parsing
- `log_work` — add timestamped log entries with optional hours
- `quick_status` — dashboard overview
- `list_calendar_events` / `create_calendar_event` — CalDAV integration
- `check_conflicts` — check all calendars for scheduling conflicts
- `quick_capture` — inbox for untriaged thoughts
- `get_dashboard` — full dashboard view with all panels
- `time_summary` — hours aggregation by project, category, date range
- `sync_issues` — GitHub sync for projects with repos

## Priority System
- Lower number = higher priority
- NULL priority = unprioritised (sorts to bottom)
- Projects with money_status = 'awaiting' float to top
- Projects with open issues float above those without

## Money Tracking
- money_status: paid, partial, awaiting, none, speculative
- Retainer projects have is_retainer=true with frequency and amount
- "Awaiting" means invoice sent but not paid — always surface these

## Status Meanings
- active: work in progress
- paused: deliberately on hold
- blocked: can't proceed, needs something
- complete: done
- killed: abandoned

## Project Categories
- consultancy: Client work (Fantata). Shows money/client fields
- podcast: Podcast planning. Episodes as issues, segments as tasks
- creative: Books, games, shows, courses. Tracks word counts
- event: Events with venue, capacity, ticket tracking
- generic: Catch-all for anything else

## Chris's Working Style
- ADHD context: break complex work into small concrete next actions
- GTD-influenced: every project should have a clear next_action
- Tends to have too many active speculative projects — flag when > 3
- Consultancy work with money attached takes priority over speculative
- If waiting_on_client > 5 days, suggest a follow-up nudge
