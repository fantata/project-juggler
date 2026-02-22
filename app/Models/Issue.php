<?php

namespace App\Models;

use App\Enums\IssueStatus;
use App\Enums\IssueUrgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'urgency',
        'raw_email',
        'github_issue_number',
        'meta',
        'calendar_event_uri',
        'scheduled_at',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'urgency' => IssueUrgency::class,
            'meta' => 'array',
            'scheduled_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(IssueTask::class)->orderBy('position');
    }

    public function completedTasksCount(): int
    {
        return $this->tasks()->where('is_complete', true)->count();
    }

    public function totalTasksCount(): int
    {
        return $this->tasks()->count();
    }
}
