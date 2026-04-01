<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_all_day' => $this->is_all_day,
            'recurrence_rule' => $this->recurrence_rule,
            'recurrence_until' => $this->recurrence_until?->toIso8601String(),
            'recurrence_parent_id' => $this->recurrence_parent_id,
            'color' => $this->color,
            'uid' => $this->uid,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
