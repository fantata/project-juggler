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
        'is_client_visible',
        'board_column',
        'position',
        'urgency',
        'due_bucket',
        'is_question',
        'answer',
        'answered_at',
        'github_issue_number',
        'guest_key',
        'guest_name',
    ];

    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'urgency' => IssueUrgency::class,
            'due_bucket' => DueBucket::class,
            'is_question' => 'boolean',
            'is_client_visible' => 'boolean',
            'answered_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /** Cards opted into the project's public client board. */
    public function scopeClientVisible($query)
    {
        return $query->where('is_client_visible', true);
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

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /** Client-created cards carry a guest name; internal cards return null. */
    public function guestAuthor(): ?string
    {
        return $this->guest_name;
    }
}
