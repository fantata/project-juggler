<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IcsFeed;
use App\Models\IcsFeedRule;
use App\Services\IcsFeedSyncService;
use Illuminate\Http\Request;

class IcsFeedRuleController extends Controller
{
    public function index(IcsFeed $feed)
    {
        return response()->json(['data' => $feed->rules]);
    }

    public function store(Request $request, IcsFeed $feed)
    {
        $validated = $request->validate([
            'field' => 'required|in:title,description,location',
            'operator' => 'required|in:contains,starts_with,matches_regex',
            'value' => 'required|string|max:255',
            'action' => 'required|in:mark_relevant,background,set_note',
            'action_value' => 'nullable|string|max:255',
            'position' => 'sometimes|integer|min:0',
        ]);

        $position = $validated['position'] ?? ($feed->rules()->max('position') + 1);

        $rule = $feed->rules()->create(array_merge($validated, ['position' => $position]));

        return response()->json(['data' => $rule]);
    }

    public function update(Request $request, IcsFeedRule $rule)
    {
        $validated = $request->validate([
            'field' => 'sometimes|in:title,description,location',
            'operator' => 'sometimes|in:contains,starts_with,matches_regex',
            'value' => 'sometimes|string|max:255',
            'action' => 'sometimes|in:mark_relevant,background,set_note',
            'action_value' => 'sometimes|nullable|string|max:255',
            'is_enabled' => 'sometimes|boolean',
            'position' => 'sometimes|integer|min:0',
        ]);

        $rule->update($validated);

        return response()->json(['data' => $rule->fresh()]);
    }

    public function destroy(IcsFeedRule $rule)
    {
        $rule->delete();

        return response()->json(['message' => 'Rule deleted']);
    }

    public function reapply(IcsFeed $feed)
    {
        $service = app(IcsFeedSyncService::class);
        $affected = $service->applyRules($feed);

        return response()->json([
            'message' => "Rules re-applied to {$affected} events",
            'affected' => $affected,
        ]);
    }
}
