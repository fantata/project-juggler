# Project Juggler — 2026 Upgrade Spec

Status: draft, not started
Owner: Chris
Companion spec: `~/Code/Projects/pulsinator/SPEC_2026_UPGRADES.md`
Source: "Bleeding-edge ideas" research doc, April 2026

## Why this spec exists

Juggler is the second brain. It already holds projects, issues, tasks, logs, daily notes, calendar events. The 2026 upgrade makes it the spine of every personal AI tool Chris runs: one memories table, one event stream, one hybrid search tool, one agent model. Everything else (Pulsinator, Dogface, Outline, the briefing emails, the Claude Desktop integration) calls into Juggler rather than reimplementing the same shape.

The Pulsinator spec covers infrastructure (deploys, observability, backups, attribution). This one is Juggler-as-platform.

## Guiding principles

1. **Build in, not bolt on.** Where the research mentions a third-party tool (Karakeep, Memos, Stevens, etc.), we read it for the shape and write our own. The only third-party dep we genuinely want is `laravel/mcp` and maybe an embedding library if Phase 4 needs it. Everything else lives in `app/` as plain Laravel code.
2. **Three front doors.** Every service layer gets Livewire for humans, JSON for clients, MCP tools for Claude. Same domain code, three surfaces. If a feature has one, finish the other two before moving on.
3. **Plaintext policy files compile to DB rules.** For any LLM-automation surface, the source of truth is a Markdown file in repo. Runtime classification is deterministic against compiled rules. LLM is not in the hot path unless it has to be.
4. **Hybrid search, cited IDs.** FTS plus vectors plus RRF. Every AI output that references Juggler data must cite the Juggler IDs it used. No citation, no claim.
5. **Append-only domain events.** New mutations go through an events table first, projections second. Undo, audit, time travel, future sync all follow.
6. **Malleable views.** A "view" is a saved filter plus a render template plus an optional action list. Hardcode nothing a user could define.
7. **Finished is allowed.** When a phase is working, stop polishing, move on.

## Phase 1: `laravel/mcp` migration

Replace `mcp-server/index.js` with a Laravel-native MCP server that shares auth, models and service classes with the web app.

**Current.** Node MCP server talks directly to MySQL. Duplicates query logic. Token is baked into Claude Desktop config.

**Target.** MCP tools live in `app/Mcp/Tools/`. Routes in `routes/ai.php`. Auth via Sanctum PATs (same tokens as the REST API). Node server deleted.

Steps:

1. Upgrade Laravel 12 → 13 (Pulsinator is already on 13; do it by hand, small diff).
2. `composer require laravel/mcp`.
3. `php artisan make:mcp-server juggler`.
4. Port every tool from `mcp-server/index.js` to a Tool class under `app/Mcp/Tools/`. One class per tool. Each tool calls a Service class, never Eloquent directly.
5. During the port, extract logic from Livewire components into `app/Services/`: `ProjectService`, `IssueService`, `TaskService`, `LogService`, `DailyNoteService`, `CalendarService`. Controllers, Livewire components and MCP tools all get thin.
6. Sanctum auth on the MCP route group. Existing REST tokens work for MCP.
7. MCP Inspector route at `/mcp/inspector` (local only, gated by `App::environment('local')`).
8. Update Claude Desktop config to `https://juggler.fantata.dev/mcp` (prod) or `https://project-juggler.test/mcp` with `-sk` (local).
9. Delete `mcp-server/` after a week of verified Claude Desktop use against the Laravel server.

**Acceptance:** every Node tool returns identical data from Laravel. REST API unchanged. `mcp-server/` directory gone.

## Phase 2: Memories + Agents (Stevens pattern, written from scratch)

Generalise the hardcoded twice-daily briefing into a first-class `Agent` model with a shared `Memory` spine.

**Schema:**

```
memories
  id
  content (text)
  tags (json)
  valid_from (datetime, nullable)
  valid_to (datetime, nullable)
  source (string) — 'manual', 'calendar', 'log', 'daily-note', 'agent-output', 'email'
  source_ref (string, nullable) — e.g. 'log:123', 'event:abc-uid'
  known_at (datetime) — when we learned it
  true_at (datetime) — when it's actually true
  created_at, updated_at

agents
  id
  name (string)
  prompt (text)
  model (string) — LLM identifier, defaults to 'gemini-2.5-flash-lite'
  scope (json) — saved filter over memories/projects/issues/tasks
  tools (json) — MCP tool allowlist
  schedule (string, nullable) — cron expression
  delivery (string) — 'email', 'telegram', 'none'
  delivery_target (string, nullable)
  memory_blocks (json) — Letta-style always-in-context blocks (persona, user profile)
  is_active (bool)
  last_run_at (datetime, nullable)
  created_at, updated_at

agent_runs
  id
  agent_id
  started_at
  finished_at (nullable)
  status (enum: running|success|failed)
  prompt_tokens, completion_tokens, cost_usd
  output (text, nullable)
  delivered_at (nullable)
  error (text, nullable)

llm_calls
  id
  agent_run_id (nullable)
  model (string)
  prompt (text)
  response (text)
  tool_calls (json, nullable)
  prompt_tokens, completion_tokens, cost_usd
  latency_ms
  created_at
```

**Key points:**

- No third-party agent framework. `App\Services\AgentRunner` is ~200 lines: load agent, build prompt from memory blocks plus scoped memories, call LLM, parse output, deliver, log.
- **Default model: Gemini 2.5 Flash-Lite.** All scheduled agents run on Flash-Lite unless the agent row overrides `model` explicitly. Upgrading a specific agent to Sonnet/Opus is a deliberate one-row change visible in the DB — no accidental drift to expensive models.
- `memory_blocks` are small labelled always-in-context strings (persona, current project state). `scope` filters memories retrieved at runtime. Letta's distinction, one table.
- Scheduling: `RunAgent` job plus `php artisan agents:run-scheduled` runs every minute in the kernel scheduler, picks up due crons. Existing AM/PM briefings become two rows.
- `llm_calls` gives searchable history across all agents plus accurate cost attribution. We own the table, no external tool.

**Three front doors:**
- Livewire: `/agents` CRUD, scope editor (raw JSON textarea v1), run-now button, last-run panel.
- JSON: `apiResource('agents')` plus `POST /agents/{id}/run`.
- MCP: `agents.list`, `agents.get`, `agents.run`, `memories.search`, `memories.create`, `memories.archive`.

**Importers (scheduled jobs, all hand-written):**
- `ImportCalendarMemories` — new calendar events → memories tagged `calendar`.
- `ImportDailyNoteMemory` — `DailyNote::saved` observer writes a memory.
- `ImportLogMemory` — `ProjectLog::saved` observer writes a memory tagged with project slugs.

All three write to the same table. Agents query across all sources.

**Acceptance:**
- Existing AM/PM briefings running as agent rows.
- Create an agent via UI, see it run on schedule.
- `llm_calls` shows every call with cost.
- Deleting an agent stops its schedule.

## Phase 3: Domain events + materialisers

Append-only event log as source of truth. Projections (existing Eloquent tables) materialise from it. Enables undo, time travel, audit, and future sync without adopting an event-sourcing library.

**Schema:**

```
domain_events
  id
  aggregate_type (string) — 'Project', 'Issue', 'Task', 'Memory'
  aggregate_id (string)
  event_type (string) — 'project.created', 'issue.status_changed'
  payload (json)
  metadata (json) — user_id, source ('web'|'mcp'|'api'|'agent'), correlation_id
  occurred_at (datetime)
  recorded_at (datetime)
```

**Implementation (all written):**
- `App\Events\DomainEvent` base class. Subclasses per event type.
- `App\Observers\*` append events on `created`, `updated`, `deleted` for each model, inside the same DB transaction as the projection write.
- `App\Services\EventStore` handles append plus replay. ~100 lines.
- `php artisan events:replay --aggregate=Project --id=12` rebuilds a projection from its event stream. Used in tests and for repair.
- Backfill on first deploy: one `*.created` event per existing row with `occurred_at = created_at`.

**Deliberately not in this phase:** multi-device sync, CRDTs, Zero/Jazz/LiveStore. Just the log. Sync can come later if it's ever actually needed.

**Acceptance:**
- Every mutation creates a domain event in the same transaction.
- `events:replay` rebuilds projections identically.
- Test suite covering create/update/delete cycles for each aggregate.

## Phase 4: Hybrid search with citations

One `juggler.search` MCP tool. FTS + vector + Reciprocal Rank Fusion. Every AI output using it cites Juggler IDs.

**Stack.** Juggler is on MySQL 8 (shared DO instance). MySQL has native FULLTEXT indexes, use them. For vectors: MySQL 9 has a vector type but the DO instance is 8, so we store embeddings as JSON arrays and do cosine similarity in PHP for now. At our scale (low thousands of rows) this is fine and means no pgvector migration.

**Schema:**

```
embeddings (polymorphic)
  id
  embeddable_type, embeddable_id
  chunk_index (int)
  content (text)
  embedding (json) — array of floats
  model (string) — which embedding model produced it
  created_at
```

**Implementation (written):**
- `App\Services\Search\HybridSearch` — accepts a query, runs FTS across memory/project/issue/log/task, runs vector similarity across `embeddings`, merges with RRF (60/40 configurable), returns top N with source IDs.
- `App\Services\Search\Embedder` — thin wrapper around an embeddings API (OpenAI or Voyage). One method: `embed(string $text): array`. This is the one external dep in this phase and it's a cheap HTTP call.
- Model observer pattern: on save of Memory/Issue/Log, queue `GenerateEmbedding` job. Chunk long text into ~500-token chunks.
- FTS indexes: `ALTER TABLE memories ADD FULLTEXT(content)`, same for issues, logs, tasks.
- Cosine similarity in PHP: ~20 lines. For low thousands of rows, fast enough. Revisit if it becomes a bottleneck.

**MCP tool contract:** `juggler.search({query, limit, types})` returns `[{type, id, title, snippet, score, url}]`. Tool description spells out: "The calling model MUST cite the returned IDs in any answer that uses this data."

**Acceptance:**
- `php artisan search:reindex` rebuilds all embeddings.
- `juggler.search` returns relevant results across types.
- Claude Desktop agents cite IDs in briefing output.

## Phase 5: Plaintext policy files → compiled DB rules

Apply the Inbox Zero pattern to every LLM automation surface. Source of truth is a Markdown file. LLM is not in the hot path.

**Pattern:**

- `storage/policies/triage.md` — human-authored rules in prose plus tagged code fences.
- `php artisan policies:compile` parses the Markdown into deterministic rules in a `policy_rules` table.
- Runtime classification is pure PHP against the rules table. LLM only runs at compile time (to turn fuzzy prose into structured rules). **No LLM fallback at runtime** — if no rule matches, that's a signal to edit the policy file. Keeps runtime cost at zero and makes behaviour fully diffable.

**Schema:**

```
policies
  id
  name (string) — 'triage', 'briefing-selection', 'project-auto-tag'
  source_path (string) — 'storage/policies/triage.md'
  compiled_at (datetime)
  compiled_hash (string)

policy_rules
  id
  policy_id
  priority (int)
  match (json) — field, operator, value
  action (string)
  action_value (json)
```

**Initial policies to build:**
- `briefing-selection.md` — "for AM briefing include: overdue P1s, today's calendar events, unpaid invoices over 30 days, active projects with no log in 7 days..." Compiles into rules the AM agent uses deterministically.
- `triage.md` — applied to inbound memory creations (from email, voice capture, etc.). Assigns tags, optionally auto-creates issues.
- `feed-rules.md` — replaces the existing ICS feed rules with a plaintext-compiled version. Current `ics_feed_rules` table becomes a compilation target.

**Acceptance:**
- Edit `storage/policies/triage.md`, run compile, see rules change in DB.
- Agent behaviour becomes diffable via git history on the Markdown file.

## Phase 6: Malleable views

Every view is a saved query plus a render template plus an optional action list. Built in from the start; no plugin system, no external deps.

**Schema:**

```
views
  id
  name (string)
  slug (string, unique)
  query (json) — filter definition over any aggregate
  render_template (string) — name of a whitelisted Blade partial ('list', 'detail-card', 'grid', 'timeline')
  actions (json) — buttons that POST to defined endpoints with current row context
  is_shared (bool) — for future multi-user, not used yet
  created_at, updated_at
```

**Implementation:**
- `App\Livewire\View` component. Takes a `View` model, renders the query result via the template, wires up action buttons.
- Query executor shared with the search service: same filter DSL as `agents.scope` and `policy_rules.match`. One DSL, used three places.
- **Render templates are a whitelist, not a sandbox.** v1 ships a small set of prewritten Blade partials (`list`, `detail-card`, `grid`, `timeline`) and the `render_template` field picks one by name. User-authored Blade is out of scope — proper template sandboxing is a security review, not a weekend, and single-user scope doesn't justify the work yet.

**Initial views to ship:**
- "Stale projects" — no log activity in 30 days, status=active.
- "Unpaid invoices" — via FreeAgent MCP when available.
- "Today" — calendar + P1 issues + overdue tasks.
- "Last week's wins" — logs in last 7 days grouped by project.

All four are currently hardcoded Livewire pages. Move them into `views` rows, delete the pages.

**Acceptance:**
- Four hardcoded pages replaced with malleable views.
- Creating a new view via UI with no code change works end-to-end.

## Phase 7: Bi-temporal fields on decisions

Every log entry and issue gets `known_at` and `true_at`. Two timestamp columns. Enables "your Q2 post-mortem said X; do you still think so?" queries.

**Migration:** add `known_at` and `true_at` to `project_logs` and `issues`. Default both to `created_at` for existing rows.

**UI:** optional fields in the edit form. Most entries will have `known_at == true_at == created_at`; explicit values matter for backdated logs ("we actually decided this last Tuesday") and for decisions-that-may-age-out.

**Vault health check agent:** new agent row, runs weekly. Surfaces:
- P1 issues with no activity in 14 days
- Projects with no log in 30 days
- Ownerless issues
- Decisions with `true_at` older than 6 months tagged `strategic` (prompt to revisit)

**Acceptance:**
- Backdated logs and issues accept distinct `known_at` and `true_at` values in the edit form.
- Existing rows default both timestamps to `created_at` after migration.
- Vault health check agent surfaces strategic decisions with `true_at` older than 6 months.

## Phase 8: Unified search across everything

One config file, one MCP tool, searches across every data source Chris owns. (Dogsheep pattern, written from scratch.)

**Config:** `config/unified_search.php` — array of source definitions, each with a query, a title field, a timestamp field, a snippet field, a category.

**Sources to include:**
- Memories (via hybrid search from Phase 4)
- Projects, issues, logs, tasks
- Calendar events + ICS feed events
- Daily notes
- Via separate MCP servers (read-only, not stored): FreeAgent invoices, Stripe customers, Dogface bookings

**External source handling:** results from external sources are never cached, indexed, or written to `embeddings`. They pass through the unified search response and are not persisted in Juggler. Juggler must not become a shadow copy of financial or customer data.

**Implementation:** `App\Services\Search\UnifiedSearch` iterates config, queries each source with the user's query, merges and ranks. Exposed as `juggler.unified_search` MCP tool.

This is the single highest-leverage tool in the whole spec. Phase 8 for a reason: depends on Phases 2, 4, 6 being solid.

## Phase 9: Dashboard widget endpoint

Expose Juggler data as HTTP widget endpoints so Pulsinator's dashboard can display Juggler content. No specific external dashboard dep required — the endpoints are plain HTML fragments that any dashboard (including a hand-rolled Pulsinator Livewire component) can embed.

**Note on Three Front Doors:** this phase is HTTP-only by design. Widgets are a distinct public-ish surface (embedded iframes/fragments on dashboards), not a core service — the Livewire and MCP equivalents already exist via the views and memories they render.

**Routes:**
- `GET /widgets/stale-projects`
- `GET /widgets/today`
- `GET /widgets/p1-issues`
- `GET /widgets/wins-this-week`

Each returns a small HTML fragment (title, list, timestamp), with optional JSON variant via `Accept: application/json`. ~15 lines per widget.

Auth: signed URLs with a per-widget secret (share with Pulsinator via env var), or a separate Sanctum token scoped to `widgets:read`.

## Priority order & rough sizing

| Phase | Effort | Depends on |
|-------|--------|------------|
| 1. laravel/mcp migration | weekend | — |
| 2. Memories + Agents | 2 weekends | 1 |
| 3. Domain events | weekend | 1 |
| 4. Hybrid search | 2 weekends | 2, 3 |
| 5. Policy files | weekend | 2 |
| 6. Malleable views | 2 weekends | 3, 4 |
| 7. Bi-temporal | evening | — (slot in anywhere after 3) |
| 8. Unified search | weekend | 2, 4 |
| 9. Widget endpoints | evening | 2, 4 |

Phases 1-3 are load-bearing. If only three phases get done in 2026, those are the three.

## What this spec deliberately does not include

- Mixpost, SendPortal, Karakeep, Memos, Rybbit, OpenPanel — read their source, don't install.
- Zero, Jazz, ElectricSQL, LiveStore — sync is not a current problem.
- FrankenPHP — Pulsinator infra spec.
- Litestream, restic, SOPS — Pulsinator spec.
- Mobile apps, voice capture, Whisper pipelines — future, not 2026.
- A `pulse` CLI binary — nice-to-have, not load-bearing.
- Any sync engine or CRDT library.

## Shipping discipline

For each phase:
1. Migration + model + service class + tests first.
2. Then one of the three front doors (usually MCP, because it's smallest).
3. Then the other two.
4. Move on. Don't polish. Mark the phase done in this file, add a dated one-line changelog entry at the bottom.

## Changelog

*(empty — first entry lands when Phase 1 ships)*
