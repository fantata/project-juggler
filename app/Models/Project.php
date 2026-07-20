<?php

namespace App\Models;

use App\Enums\MoneyStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\RetainerFrequency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        'ai_context',
        'ai_context_updated_at',
        'last_touched_at',
        'share_token',
        'share_enabled',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'status' => ProjectStatus::class,
            'money_status' => MoneyStatus::class,
            'money_value' => 'decimal:2',
            'deadline' => 'date',
            'ai_context_updated_at' => 'datetime',
            'last_touched_at' => 'datetime',
            'waiting_on_client' => 'boolean',
            'is_retainer' => 'boolean',
            'retainer_frequency' => RetainerFrequency::class,
            'retainer_amount' => 'decimal:2',
            'priority' => 'integer',
            'share_enabled' => 'boolean',
        ];
    }

    /**
     * The public client-board URL, or null if sharing is off. The token in the
     * path is the only auth — treat it like a password in a link.
     */
    public function shareUrl(): ?string
    {
        if (! $this->share_enabled || ! $this->share_token) {
            return null;
        }

        return route('board.show', $this->share_token);
    }

    /** Turn the client board on, minting a link the first time. */
    public function enableClientBoard(): void
    {
        $this->update([
            'share_token' => $this->share_token ?: Str::random(48),
            'share_enabled' => true,
        ]);
    }

    /** Mint a fresh link, instantly invalidating the old one. */
    public function rotateShareToken(): void
    {
        $this->update([
            'share_token' => Str::random(48),
            'share_enabled' => true,
        ]);
    }

    /** Switch the board off. The link 404s until re-enabled. */
    public function disableClientBoard(): void
    {
        $this->update(['share_enabled' => false]);
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
