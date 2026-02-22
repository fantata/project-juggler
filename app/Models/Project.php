<?php

namespace App\Models;

use App\Enums\MoneyStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\RetainerFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'waiting_on_client',
        'is_retainer',
        'retainer_frequency',
        'retainer_amount',
        'priority',
        'money_status',
        'money_value',
        'deadline',
        'next_action',
        'notes',
        'github_repo',
        'last_touched_at',
        'category',
        'meta',
        'calendar_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'status' => ProjectStatus::class,
            'money_status' => MoneyStatus::class,
            'money_value' => 'decimal:2',
            'deadline' => 'date',
            'last_touched_at' => 'datetime',
            'waiting_on_client' => 'boolean',
            'is_retainer' => 'boolean',
            'retainer_frequency' => RetainerFrequency::class,
            'retainer_amount' => 'decimal:2',
            'priority' => 'integer',
            'meta' => 'array',
        ];
    }

    public function categoryConfig(): array
    {
        return config('project-categories.' . ($this->category ?? 'consultancy'), config('project-categories.consultancy'));
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->categoryConfig()['label'] ?? 'Project';
    }

    public function getIssueLabelAttribute(): string
    {
        return $this->categoryConfig()['issue_label'] ?? 'Issue';
    }

    public function getTaskLabelAttribute(): string
    {
        return $this->categoryConfig()['task_label'] ?? 'Task';
    }

    public function shouldShowField(string $field): bool
    {
        return in_array($field, $this->categoryConfig()['show_fields'] ?? []);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProjectLog::class)->orderByDesc('created_at');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class)->orderByDesc('created_at');
    }

    public function markTouched(): bool
    {
        $this->last_touched_at = now();
        return $this->save();
    }
}
