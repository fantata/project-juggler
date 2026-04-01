<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'waiting_on_client' => $this->waiting_on_client,
            'is_retainer' => $this->is_retainer,
            'retainer_frequency' => $this->retainer_frequency?->value,
            'retainer_amount' => $this->retainer_amount,
            'priority' => $this->priority,
            'money_status' => $this->money_status?->value,
            'money_value' => $this->money_value,
            'deadline' => $this->deadline?->toDateString(),
            'next_action' => $this->next_action,
            'notes' => $this->notes,
            'github_repo' => $this->github_repo,
            'last_touched_at' => $this->last_touched_at?->toIso8601String(),
            'open_issue_count' => $this->when(isset($this->open_issue_count), $this->open_issue_count),
            'issues' => IssueResource::collection($this->whenLoaded('issues')),
            'logs' => LogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
