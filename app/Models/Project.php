<?php

namespace App\Models;

use App\Enums\MoneyStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\RetainerFrequency;
use Illuminate\Database\Eloquent\Builder;
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
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProjectLog::class)->orderByDesc('created_at');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class)->orderByDesc('created_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['complete', 'killed']);
    }

    public function markTouched(): bool
    {
        $this->last_touched_at = now();
        return $this->save();
    }
}
