# Project Juggler API Reference

Base URL: `https://project-juggler.chrisread.me/api` (production) or `http://project-juggler.test/api` (local)

## Authentication

All requests require a Bearer token in the Authorization header:

```
Authorization: Bearer <token>
```

Generate a token from Profile > API Token in the web UI.

## Endpoints

### GET /status

Overview dashboard — project counts, upcoming deadlines, money owed.

Response:
```json
{
  "active_projects": 10,
  "blocked_projects": 0,
  "waiting_on_client": 2,
  "open_issues": 3,
  "incomplete_tasks": 7,
  "upcoming_deadlines": [
    { "id": 12, "name": "EFATS", "deadline": "2026-02-20", "status": "active" }
  ],
  "awaiting_money": [
    { "id": 1, "name": "Robbie the Trader", "money_value": "1100.00" }
  ],
  "total_awaiting_value": 3450
}
```

---

### GET /projects

List projects. Excludes complete/killed by default.

Query params:
- `type` — filter: `client`, `personal`, `speculative`
- `status` — filter: `active`, `paused`, `blocked`, `complete`, `killed`
- `money_status` — filter: `paid`, `partial`, `awaiting`, `none`, `speculative`
- `waiting_on_client` — `1` to show only waiting
- `is_retainer` — `1` to show only retainers
- `include_completed` — `1` to include complete/killed projects

Response: `{ "data": [ ProjectResource, ... ] }`

### POST /projects

Create a project.

Body (JSON):
```json
{
  "name": "New Project",
  "type": "client",
  "status": "active",
  "money_status": "none",
  "money_value": 500.00,
  "deadline": "2026-05-01",
  "next_action": "Send proposal",
  "notes": "Optional notes",
  "priority": 1,
  "waiting_on_client": false,
  "is_retainer": false,
  "retainer_frequency": "monthly",
  "retainer_amount": 600.00,
  "github_repo": "org/repo"
}
```

Required: `name`, `type`. Everything else optional. Defaults: `status=active`, `money_status=none`.

### GET /projects/{id}

Single project with open issues (including their tasks) and 10 most recent logs.

### PATCH /projects/{id}

Partial update. Send only the fields you want to change.

### DELETE /projects/{id}

Delete a project and all its issues/tasks/logs.

---

### GET /projects/{id}/issues

List issues for a project.

Query params:
- `status` — filter: `open`, `in_progress`, `done`

Response includes tasks nested under each issue.

### POST /projects/{id}/issues

Create an issue. Two modes:

**Manual:**
```json
{
  "title": "Fix login page",
  "description": "The form doesn't validate",
  "urgency": "high",
  "status": "open"
}
```

**AI email parsing** — send `raw_email` and it auto-extracts title, description, urgency, and tasks:
```json
{
  "raw_email": "Hi Chris, the checkout is broken again..."
}
```

### PATCH /issues/{id}

Update issue. Fields: `title`, `description`, `status`, `urgency`.

### DELETE /issues/{id}

Delete issue and its tasks.

---

### GET /tasks

Cross-project task list. Shows all incomplete tasks from active projects.

Query params:
- `include_completed` — `1` to include completed tasks

Each task includes `project_name` and `issue_title` for context.

### POST /issues/{id}/tasks

Add a task to an issue.

```json
{
  "description": "Update the CSS",
  "position": 1
}
```

Required: `description`. Position auto-increments if omitted.

### PATCH /tasks/{id}

Update task. Fields: `description`, `is_complete` (boolean), `position`.

### DELETE /tasks/{id}

Delete a task.

---

### GET /projects/{id}/logs

List work logs for a project (newest first).

Query params:
- `limit` — max number of logs to return

### POST /projects/{id}/logs

Add a work log entry.

```json
{
  "entry": "Spent 2 hours on the checkout refactor",
  "hours": 2.0
}
```

Required: `entry`. `hours` is optional.

---

## Project Resource Shape

All project responses use this shape:

```json
{
  "id": 1,
  "name": "Project Name",
  "type": "client",
  "status": "active",
  "waiting_on_client": false,
  "is_retainer": false,
  "retainer_frequency": null,
  "retainer_amount": null,
  "priority": 1,
  "money_status": "awaiting",
  "money_value": "1100.00",
  "deadline": "2026-05-01",
  "next_action": "Send proposal",
  "notes": "...",
  "github_repo": "org/repo",
  "last_touched_at": "2026-03-31T12:00:00+00:00",
  "open_issue_count": 3,
  "issues": [],
  "logs": [],
  "created_at": "2026-01-16T17:12:34+00:00",
  "updated_at": "2026-03-31T12:00:00+00:00"
}
```

`issues` and `logs` are only included on single-project responses (GET /projects/{id}).

---

### GET /events

List calendar events in a date range. Recurring events are expanded into individual occurrences.

Query params:
- `from` — start date (default: start of current month)
- `to` — end date (default: end of current month)

Each occurrence includes `is_recurring: true/false`.

### POST /events

Create a calendar event.

```json
{
  "title": "Sprint planning",
  "starts_at": "2026-04-01T10:00:00",
  "ends_at": "2026-04-01T11:30:00",
  "location": "Norwich office",
  "description": "Q2 sprint kickoff",
  "is_all_day": false,
  "recurrence_rule": "FREQ=WEEKLY;BYDAY=MO",
  "recurrence_until": "2026-06-30T00:00:00",
  "color": "#C2714F"
}
```

Required: `title`, `starts_at`. Recurrence uses RFC 5545 RRULE format.

### GET /events/{id}

Single event.

### PATCH /events/{id}

Partial update. Send only fields you want to change.

### DELETE /events/{id}

Delete an event. Add `?all_future=1` to also delete recurrence exceptions.

---

### ICS Feed (Export)

```
GET /ics/{token}.ics
```

Public URL (no API auth). Generate the token from Profile > Calendar Feed. Only includes native calendar events — never external feed events.

---

### GET /feeds

List all external ICS feed subscriptions with sync status and event counts.

### POST /feeds

Subscribe to an external ICS feed. Immediately syncs on creation.

```json
{
  "name": "Dogface Improv",
  "url": "https://example.com/calendar.ics",
  "color": "#6B8F71",
  "sync_interval_minutes": 60
}
```

Required: `name`, `url`. Sync interval minimum 15 minutes.

### GET /feeds/{id}

Single feed with rules.

### PATCH /feeds/{id}

Update feed. Fields: `name`, `url`, `color`, `is_enabled`, `sync_interval_minutes`.

### DELETE /feeds/{id}

Delete feed and all its events/rules.

### POST /feeds/{id}/sync

Trigger manual sync of a feed. Returns updated feed with sync stats.

### GET /feeds/{id}/events

List events from this feed in a date range.

Query params:
- `from` — start date (default: start of current month)
- `to` — end date (default: end of current month)

Each event includes `is_backgrounded`, `is_relevant`, and `relevance_note` fields set by rules.

---

### GET /feeds/{id}/rules

List rules for a feed.

### POST /feeds/{id}/rules

Create a rule.

```json
{
  "field": "description",
  "operator": "contains",
  "value": "Chris",
  "action": "mark_relevant",
  "action_value": null
}
```

Fields: `title`, `description`, `location`
Operators: `contains`, `starts_with`, `matches_regex`
Actions: `mark_relevant`, `background`, `set_note` (use `action_value` with `set_note`)

### PATCH /rules/{id}

Update a rule.

### DELETE /rules/{id}

Delete a rule.

### POST /feeds/{id}/rules/reapply

Re-apply all rules to existing events in this feed. Useful after adding/changing rules.

---

## Enum Values

- **Project type**: `client`, `personal`, `speculative`
- **Project status**: `active`, `paused`, `blocked`, `complete`, `killed`
- **Money status**: `paid`, `partial`, `awaiting`, `none`, `speculative`
- **Issue status**: `open`, `in_progress`, `done`
- **Issue urgency**: `low`, `medium`, `high`
- **Retainer frequency**: `monthly`, `yearly`
