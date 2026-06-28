<?php

namespace App\Models;

use App\Enums\DueBucket;
use App\Enums\IssueStatus;
use App\Enums\IssueUrgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Issue extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'assignee_id',
        'kind',
        'board_column',
        'position',
        'urgency',
        'due_bucket',
        'is_question',
        'answer',
        'answered_at',
        'github_issue_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'urgency' => IssueUrgency::class,
            'due_bucket' => DueBucket::class,
            'is_question' => 'boolean',
            'answered_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(IssueTask::class)->orderBy('position');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }
}
