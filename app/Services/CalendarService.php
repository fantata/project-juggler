<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\IcsFeedEvent;
use App\Models\Issue;
use App\Models\IssueTask;
use Carbon\CarbonImmutable;

class CalendarService
{
    public function inRange(string $from, string $to): array
    {
        $native = CalendarEvent::query()
            ->whereDate('starts_at', '>=', $from)
            ->whereDate('starts_at', '<=', $to)
            ->orderBy('starts_at')
            ->get(['title', 'starts_at', 'ends_at', 'is_all_day', 'location', 'description'])
            ->map(fn ($e) => array_merge($e->toArray(), ['source' => 'native']));

        $feed = IcsFeedEvent::query()
            ->where('is_backgrounded', false)
            ->whereDate('starts_at', '>=', $from)
            ->whereDate('starts_at', '<=', $to)
            ->with('feed:id,name')
            ->orderBy('starts_at')
            ->get()
            ->map(fn ($e) => [
                'title' => $e->title,
                'starts_at' => $e->starts_at,
                'ends_at' => $e->ends_at,
                'is_all_day' => $e->is_all_day,
                'location' => $e->location,
                'description' => $e->description,
                'feed_name' => $e->feed?->name,
                'source' => 'feed',
            ]);

        return $native->concat($feed)
            ->sortBy(fn ($e) => $e['starts_at'])
            ->values()
            ->all();
    }

    public function today(): array
    {
        $today = CarbonImmutable::today();
        $dateStr = $today->toDateString();

        $native = CalendarEvent::query()
            ->where(function ($q) use ($dateStr) {
                $q->whereDate('starts_at', $dateStr)
                  ->orWhere(function ($q) use ($dateStr) {
                      $q->where('is_all_day', true)
                        ->whereDate('starts_at', '<=', $dateStr)
                        ->where(function ($q) use ($dateStr) {
                            $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $dateStr);
                        });
                  });
            })
            ->orderByDesc('is_all_day')
            ->orderBy('starts_at')
            ->get(['title', 'starts_at', 'ends_at', 'is_all_day', 'location'])
            ->map(fn ($e) => array_merge($e->toArray(), ['source' => 'native']));

        $feed = IcsFeedEvent::query()
            ->where('is_backgrounded', false)
            ->where(function ($q) use ($dateStr) {
                $q->whereDate('starts_at', $dateStr)
                  ->orWhere(function ($q) use ($dateStr) {
                      $q->where('is_all_day', true)
                        ->whereDate('starts_at', '<=', $dateStr)
                        ->where(function ($q) use ($dateStr) {
                            $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $dateStr);
                        });
                  });
            })
            ->with('feed:id,name')
            ->orderByDesc('is_all_day')
            ->orderBy('starts_at')
            ->get()
            ->map(fn ($e) => [
                'title' => $e->title,
                'starts_at' => $e->starts_at,
                'ends_at' => $e->ends_at,
                'is_all_day' => $e->is_all_day,
                'location' => $e->location,
                'feed_name' => $e->feed?->name,
                'source' => 'feed',
            ]);

        $events = $native->concat($feed)
            ->sortBy([['is_all_day', 'desc'], ['starts_at', 'asc']])
            ->values()
            ->all();

        $openTasks = IssueTask::query()
            ->where('is_complete', false)
            ->whereHas('issue.project', fn ($q) => $q->whereNotIn('status', ['complete', 'killed']))
            ->count();

        return [
            'date' => $dateStr,
            'day' => $today->format('l'),
            'events' => $events,
            'open_tasks' => $openTasks,
        ];
    }

    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::create(array_merge($data, [
            'uid' => $data['uid'] ?? sprintf('mcp-%d-%s@juggler', now()->timestamp, bin2hex(random_bytes(4))),
        ]));
    }
}
