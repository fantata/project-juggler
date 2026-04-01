<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogResource;
use App\Models\Project;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $query = $project->logs();

        if ($request->has('limit')) {
            $query->limit((int) $request->limit);
        }

        return LogResource::collection($query->get());
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'entry' => 'required|string',
            'hours' => 'sometimes|nullable|numeric|min:0',
        ]);

        $log = $project->logs()->create($validated);
        $project->markTouched();

        return new LogResource($log);
    }
}
