# Morning Briefing

When Chris says "morning" or "standup" or "what's on today", run this routine:

1. Call `get_dashboard` to get the full overview
2. Call `list_calendar_events` for today and tomorrow
3. Call `list_projects` with status=active to check next_actions

## Report Format
Lead with today's calendar — what's time-bound comes first.
Then priorities: what should Chris focus on today? Pick max 3 things.
Then flags: anything overdue, any money outstanding, anything blocked.
Keep it brief. Don't list every project — just what needs attention.

## Decision Rules
- If there's a client deadline this week, that's priority 1
- If money is awaiting and > 7 days, flag it
- If a project has no next_action, flag it ("X has no next action — what's the move?")
- If Chris has a corporate gig this week, remind about prep
- Don't nag about speculative projects unless they have deadlines
- If it's a high-context-switch day (3+ different types of commitment), flag it:
  "Heads up, today's a high-context-switch day. Consider batching similar tasks
  around your fixed commitments rather than trying to deep-work between them."
