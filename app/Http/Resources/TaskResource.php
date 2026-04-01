<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'issue_id' => $this->issue_id,
            'description' => $this->description,
            'is_complete' => $this->is_complete,
            'position' => $this->position,
            'is_ai_generated' => $this->is_ai_generated,
            'project_name' => $this->when(
                $this->relationLoaded('issue') && $this->issue->relationLoaded('project'),
                fn () => $this->issue->project->name
            ),
            'issue_title' => $this->when(
                $this->relationLoaded('issue'),
                fn () => $this->issue->title
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
