<?php

namespace App\Livewire;

use App\Models\ExternalCalendarEvent;
use App\Models\Issue;
use App\Models\Project;
use App\Services\CalendarService;
use Livewire\Component;

class ScreenDashboard extends Component
{
    public string $quickCapture = '';

    public function addQuickCapture(): void
    {
        if (empty(trim($this->quickCapture))) {
            return;
        }

        // Find or create an "Inbox" project
        $inbox = Project::firstOrCreate(
            ['name' => 'Inbox'],
            [
                'type' => 'personal',
                'status' => 'active',
                'money_status' => 'none',
                'category' => 'generic',
                'last_touched_at' => now(),
            ]
        );

        $inbox->issues()->create([
            'title' => trim($this->quickCapture),
            'status' => 'open',
            'urgency' => 'medium',
        ]);

        $inbox->update(['last_touched_at' => now()]);
        $this->quickCapture = '';
    }

    private function getPriorities(): \Illuminate\Support\Collection
    {
        // Open tasks from all active projects, sorted by urgency and project priority
        return Issue::with('project')
            ->whereHas('project', fn($q) => $q->where('status', 'active'))
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByRaw("CASE urgency WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN (SELECT priority FROM projects WHERE projects.id = issues.project_id) IS NULL THEN 1 ELSE 0 END")
            ->orderByRaw("(SELECT priority FROM projects WHERE projects.id = issues.project_id) ASC")
            ->limit(15)
            ->get();
    }

    private function getUpcomingEvents(): \Illuminate\Support\Collection
    {
        $events = collect();

        // Try to get local CalDAV events
        try {
            $user = auth()->user();
            $calendarService = new CalendarService();
            $principalUri = 'principals/' . $user->email;
            $localEvents = $calendarService->getAllEventsInRange(
                $principalUri,
                now(),
                now()->addDays(3)
            );

            foreach ($localEvents as $event) {
                $events->push((object) [
                    'title' => $event['title'],
                    'start' => $event['start'],
                    'end' => $event['end'],
                    'location' => $event['location'] ?? null,
                    'all_day' => $event['all_day'] ?? false,
                    'source' => $event['calendar_name'] ?? 'local',
                    'project_id' => $event['project_id'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            // CalDAV not set up yet — continue without local events
        }

        // Get external cached events
        $externalEvents = ExternalCalendarEvent::where('start', '>=', now())
            ->where('start', '<=', now()->addDays(3))
            ->orderBy('start')
            ->get();

        foreach ($externalEvents as $event) {
            $events->push((object) [
                'title' => $event->title,
                'start' => $event->start,
                'end' => $event->end,
                'location' => $event->location,
                'all_day' => $event->all_day,
                'source' => $event->source,
                'project_id' => null,
            ]);
        }

        return $events->sortBy('start')->values();
    }

    private function getActiveProjects(): \Illuminate\Support\Collection
    {
        return Project::where('status', 'active')
            ->withCount(['issues as open_issue_count' => fn($q) => $q->whereIn('status', ['open', 'in_progress'])])
            ->orderByRaw("CASE WHEN priority IS NULL THEN 1 ELSE 0 END")
            ->orderBy('priority', 'asc')
            ->get();
    }

    private function getMoneyStatus(): array
    {
        $awaiting = Project::where('money_status', 'awaiting')
            ->whereNotIn('status', ['complete', 'killed'])
            ->get();

        $totalAwaiting = $awaiting->sum('money_value');

        $overdue = $awaiting->filter(fn($p) => $p->deadline && $p->deadline->isPast());
        $totalOverdue = $overdue->sum('money_value');

        $retainersDue = Project::where('is_retainer', true)
            ->where('status', 'active')
            ->where('money_status', 'awaiting')
            ->count();

        return [
            'awaiting_total' => $totalAwaiting,
            'awaiting_projects' => $awaiting,
            'overdue_total' => $totalOverdue,
            'retainers_due' => $retainersDue,
        ];
    }

    private function getUpcomingDeadlines(): \Illuminate\Support\Collection
    {
        return Project::whereNotNull('deadline')
            ->where('deadline', '>=', now()->subDay())
            ->where('deadline', '<=', now()->addDays(14))
            ->whereNotIn('status', ['complete', 'killed'])
            ->orderBy('deadline')
            ->get();
    }

    public function render()
    {
        return view('livewire.screen-dashboard', [
            'priorities' => $this->getPriorities(),
            'calendarEvents' => $this->getUpcomingEvents(),
            'activeProjects' => $this->getActiveProjects(),
            'moneyStatus' => $this->getMoneyStatus(),
            'deadlines' => $this->getUpcomingDeadlines(),
        ])->layout('layouts.screen');
    }
}
