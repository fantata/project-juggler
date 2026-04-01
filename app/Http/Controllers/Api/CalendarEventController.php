<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CalendarEventResource;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarEventController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->has('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->has('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        $events = CalendarEvent::inRange($from, $to)
            ->whereNull('recurrence_parent_id')
            ->orderBy('starts_at')
            ->get();

        // Expand recurring events into individual occurrences
        $expanded = collect();

        foreach ($events as $event) {
            if ($event->recurrence_rule) {
                $occurrences = $event->occurrencesInRange($from, $to);
                $duration = $event->ends_at ? $event->starts_at->diffInSeconds($event->ends_at) : null;

                foreach ($occurrences as $occurrence) {
                    $expanded->push([
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'location' => $event->location,
                        'starts_at' => $occurrence->toIso8601String(),
                        'ends_at' => $duration ? $occurrence->copy()->addSeconds($duration)->toIso8601String() : null,
                        'is_all_day' => $event->is_all_day,
                        'is_recurring' => true,
                        'recurrence_rule' => $event->recurrence_rule,
                        'color' => $event->color,
                        'uid' => $event->uid,
                    ]);
                }
            } else {
                $expanded->push([
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'location' => $event->location,
                    'starts_at' => $event->starts_at->toIso8601String(),
                    'ends_at' => $event->ends_at?->toIso8601String(),
                    'is_all_day' => $event->is_all_day,
                    'is_recurring' => false,
                    'recurrence_rule' => null,
                    'color' => $event->color,
                    'uid' => $event->uid,
                ]);
            }
        }

        return response()->json(['data' => $expanded->sortBy('starts_at')->values()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'location' => 'sometimes|nullable|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'sometimes|nullable|date|after_or_equal:starts_at',
            'is_all_day' => 'sometimes|boolean',
            'recurrence_rule' => 'sometimes|nullable|string|max:255',
            'recurrence_until' => 'sometimes|nullable|date',
            'color' => 'sometimes|nullable|string|max:7',
        ]);

        $event = CalendarEvent::create($validated);

        return new CalendarEventResource($event);
    }

    public function show(CalendarEvent $event)
    {
        return new CalendarEventResource($event);
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'location' => 'sometimes|nullable|string|max:255',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|nullable|date',
            'is_all_day' => 'sometimes|boolean',
            'recurrence_rule' => 'sometimes|nullable|string|max:255',
            'recurrence_until' => 'sometimes|nullable|date',
            'color' => 'sometimes|nullable|string|max:7',
        ]);

        $event->update($validated);

        return new CalendarEventResource($event->fresh());
    }

    public function destroy(Request $request, CalendarEvent $event)
    {
        // If this is a recurring event, optionally delete only future occurrences
        if ($request->boolean('all_future') && $event->recurrence_rule) {
            $event->recurrenceExceptions()->delete();
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted']);
    }
}
