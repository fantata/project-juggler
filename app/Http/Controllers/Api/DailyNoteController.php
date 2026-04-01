<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyNote;
use Illuminate\Http\Request;

class DailyNoteController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->get('days', 14);
        $days = max(1, min(365, $days));

        $notes = DailyNote::where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->get(['id', 'body', 'energy_level', 'created_at']);

        return response()->json(['data' => $notes]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'energy_level' => 'sometimes|nullable|in:low,medium,high',
        ]);

        $note = DailyNote::create($validated);

        return response()->json($note, 201);
    }
}
