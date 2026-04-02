<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\IcsFeed;
use App\Models\IcsFeedEvent;
use App\Models\Project;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class Calendar extends Component
{
    // Event form
    public bool $showEventForm = false;
    public ?int $editingEventId = null;
    public string $eventTitle = '';
    public string $eventDescription = '';
    public string $eventLocation = '';
    public string $eventStartsAt = '';
    public string $eventEndsAt = '';
    public bool $eventIsAllDay = false;
    public string $eventRecurrenceRule = '';
    public string $eventColor = '';

    // Layer toggles
    public bool $showDeadlines = true;
    public array $enabledFeeds = [];

    public function mount(): void
    {
        $this->enabledFeeds = IcsFeed::where('is_enabled', true)->pluck('id')->toArray();
    }

    #[On('calendar-fetch-events')]
    public function fetchEvents(string $start, string $end): array
    {
        $from = Carbon::parse($start);
        $to = Carbon::parse($end);
        $events = [];

        // Native events
        $nativeEvents = CalendarEvent::inRange($from, $to)
            ->whereNull('recurrence_parent_id')
            ->get();

        foreach ($nativeEvents as $event) {
            if ($event->recurrence_rule) {
                $duration = $event->ends_at ? $event->starts_at->diffInSeconds($event->ends_at) : 3600;
                foreach ($event->occurrencesInRange($from, $to) as $occurrence) {
                    $events[] = [
                        'id' => 'native-' . $event->id . '-' . $occurrence->timestamp,
                        'title' => $event->title,
                        'start' => $occurrence->toIso8601String(),
                        'end' => $occurrence->copy()->addSeconds($duration)->toIso8601String(),
                        'allDay' => $event->is_all_day,
                        'color' => $event->color ?? '#C2714F',
                        'extendedProps' => [
                            'type' => 'native',
                            'eventId' => $event->id,
                            'location' => $event->location,
                            'description' => $event->description,
                        ],
                    ];
                }
            } else {
                $events[] = [
                    'id' => 'native-' . $event->id,
                    'title' => $event->title,
                    'start' => $event->starts_at->toIso8601String(),
                    'end' => $event->ends_at?->toIso8601String(),
                    'allDay' => $event->is_all_day,
                    'color' => $event->color ?? '#C2714F',
                    'extendedProps' => [
                        'type' => 'native',
                        'eventId' => $event->id,
                        'location' => $event->location,
                        'description' => $event->description,
                    ],
                ];
            }
        }

        // Project deadlines
        if ($this->showDeadlines) {
            $deadlines = Project::whereNotNull('deadline')
                ->whereNotIn('status', ['complete', 'killed'])
                ->whereBetween('deadline', [$from, $to])
                ->get(['id', 'name', 'deadline']);

            foreach ($deadlines as $project) {
                $events[] = [
                    'id' => 'deadline-' . $project->id,
                    'title' => '📌 ' . $project->name,
                    'start' => $project->deadline->toDateString(),
                    'allDay' => true,
                    'color' => '#8B6914',
                    'borderColor' => '#A68B4B',
                    'display' => 'block',
                    'extendedProps' => [
                        'type' => 'deadline',
                        'projectId' => $project->id,
                    ],
                ];
            }
        }

        // External feed events — batch-load feeds instead of querying per feed
        if (! empty($this->enabledFeeds)) {
            $feeds = IcsFeed::whereIn('id', $this->enabledFeeds)->get(['id', 'name', 'color']);
            $feedEventsAll = IcsFeedEvent::whereIn('ics_feed_id', $this->enabledFeeds)
                ->inRange($from, $to)
                ->get();

            $feedMap = $feeds->keyBy('id');

            foreach ($feedEventsAll as $feedEvent) {
                $feed = $feedMap[$feedEvent->ics_feed_id] ?? null;
                if (! $feed) continue;

                $events[] = [
                    'id' => 'feed-' . $feedEvent->id,
                    'title' => $feedEvent->title,
                    'start' => $feedEvent->starts_at->toIso8601String(),
                    'end' => $feedEvent->ends_at?->toIso8601String(),
                    'allDay' => $feedEvent->is_all_day,
                    'color' => $feed->color ?? '#9CA3AF',
                    'classNames' => $feedEvent->is_backgrounded ? ['opacity-30'] : ($feedEvent->is_relevant ? ['ring-2', 'ring-terracotta-400'] : []),
                    'extendedProps' => [
                        'type' => 'feed',
                        'feedName' => $feed->name,
                        'feedId' => $feed->id,
                        'isRelevant' => $feedEvent->is_relevant,
                        'isBackgrounded' => $feedEvent->is_backgrounded,
                        'relevanceNote' => $feedEvent->relevance_note,
                        'location' => $feedEvent->location,
                        'description' => $feedEvent->description,
                    ],
                ];
            }
        }

        return $events;
    }

    public function openNewEvent(string $date = ''): void
    {
        $this->reset(['editingEventId', 'eventTitle', 'eventDescription', 'eventLocation', 'eventRecurrenceRule', 'eventColor']);
        $this->eventStartsAt = $date ?: now()->format('Y-m-d\TH:i');
        $this->eventEndsAt = $date ? Carbon::parse($date)->addHour()->format('Y-m-d\TH:i') : now()->addHour()->format('Y-m-d\TH:i');
        $this->eventIsAllDay = false;
        $this->showEventForm = true;
    }

    public function editEvent(int $id): void
    {
        $event = CalendarEvent::findOrFail($id);
        $this->editingEventId = $event->id;
        $this->eventTitle = $event->title;
        $this->eventDescription = $event->description ?? '';
        $this->eventLocation = $event->location ?? '';
        $this->eventStartsAt = $event->starts_at->format('Y-m-d\TH:i');
        $this->eventEndsAt = $event->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->eventIsAllDay = $event->is_all_day;
        $this->eventRecurrenceRule = $event->recurrence_rule ?? '';
        $this->eventColor = $event->color ?? '';
        $this->showEventForm = true;
    }

    public function saveEvent(): void
    {
        $this->validate([
            'eventTitle' => 'required|string|max:255',
            'eventStartsAt' => 'required|date',
        ]);

        $data = [
            'title' => $this->eventTitle,
            'description' => $this->eventDescription ?: null,
            'location' => $this->eventLocation ?: null,
            'starts_at' => $this->eventStartsAt,
            'ends_at' => $this->eventEndsAt ?: null,
            'is_all_day' => $this->eventIsAllDay,
            'recurrence_rule' => $this->eventRecurrenceRule ?: null,
            'color' => $this->eventColor ?: null,
        ];

        if ($this->editingEventId) {
            CalendarEvent::findOrFail($this->editingEventId)->update($data);
        } else {
            CalendarEvent::create($data);
        }

        $this->showEventForm = false;
        $this->dispatch('calendar-refresh');
    }

    public function deleteEvent(): void
    {
        if ($this->editingEventId) {
            CalendarEvent::findOrFail($this->editingEventId)->delete();
            $this->showEventForm = false;
            $this->dispatch('calendar-refresh');
        }
    }

    public function render()
    {
        $feeds = IcsFeed::where('is_enabled', true)->get(['id', 'name', 'color']);

        return view('livewire.calendar', [
            'feeds' => $feeds,
        ]);
    }
}
