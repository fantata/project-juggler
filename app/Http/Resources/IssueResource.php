<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'urgency' => $this->urgency?->value,
            'github_issue_number' => $this->github_issue_number,
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'completed_tasks' => $this->when(
                $this->relationLoaded('tasks'),
                fn () => $this->tasks->where('is_complete', true)->count()
            ),
            'total_tasks' => $this->when(
                $this->relationLoaded('tasks'),
                fn () => $this->tasks->count()
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
