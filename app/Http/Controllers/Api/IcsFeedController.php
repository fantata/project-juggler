<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IcsFeed;
use App\Models\IcsFeedEvent;
use App\Services\IcsFeedSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IcsFeedController extends Controller
{
    public function index()
    {
        $feeds = IcsFeed::withCount('events')->get();

        return response()->json(['data' => $feeds]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2000',
            'color' => 'sometimes|nullable|string|max:7',
            'sync_interval_minutes' => 'sometimes|integer|min:15|max:1440',
        ]);

        $feed = IcsFeed::create($validated);

        // Immediately sync the new feed
        $service = app(IcsFeedSyncService::class);
        $stats = $service->syncFeed($feed);

        return response()->json([
            'data' => $feed->fresh()->loadCount('events'),
            'sync' => $stats,
        ]);
    }

    public function show(IcsFeed $feed)
    {
        $feed->loadCount('events');
        $feed->load('rules');

        return response()->json(['data' => $feed]);
    }

    public function update(Request $request, IcsFeed $feed)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:2000',
            'color' => 'sometimes|nullable|string|max:7',
            'is_enabled' => 'sometimes|boolean',
            'sync_interval_minutes' => 'sometimes|integer|min:15|max:1440',
        ]);

        $feed->update($validated);

        return response()->json(['data' => $feed->fresh()]);
    }

    public function destroy(IcsFeed $feed)
    {
        $feed->delete();

        return response()->json(['message' => 'Feed deleted']);
    }

    public function sync(IcsFeed $feed)
    {
        $service = app(IcsFeedSyncService::class);
        $stats = $service->syncFeed($feed);

        return response()->json([
            'data' => $feed->fresh()->loadCount('events'),
            'sync' => $stats,
        ]);
    }

    public function events(Request $request, IcsFeed $feed)
    {
        $from = $request->has('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->has('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        $events = $feed->events()
            ->inRange($from, $to)
            ->orderBy('starts_at')
            ->get();

        return response()->json(['data' => $events]);
    }
}
