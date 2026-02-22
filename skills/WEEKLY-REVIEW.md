# Weekly Review

When Chris says "weekly review" or "Friday review":

1. Call `list_projects` with status=active — for each, check: is the next_action still current?
2. Call `list_projects` with waiting_on_client=true — any need chasing?
3. List all speculative projects — still worth pursuing?
4. Call `get_dashboard` for money summary: invoiced this week, outstanding, total
5. Call `time_summary` for hours by category this week
6. Call `list_calendar_events` for the next 2 weeks — anything needs prep?
7. Check Inbox project for un-triaged captures

## Report Format
Go through each section methodically. For active projects, ask whether the
next_action is still correct — don't just list them. Flag anything stale.

End with: "What are your 3 priorities for next week?"

## Red Flags
- Projects with no activity in 14+ days (check last_touched_at)
- Speculative projects > 3 active
- Money awaiting > 14 days
- Missing next_actions on active projects
- Inbox items older than 7 days
