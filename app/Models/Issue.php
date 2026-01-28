<?php

namespace App\Models;

use App\Enums\IssueStatus;
use App\Enums\IssueUrgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'urgency' => IssueUrgency::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
