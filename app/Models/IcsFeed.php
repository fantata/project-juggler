<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IcsFeed extends Model
{
    protected $fillable = [
        'name',
        'url',
        'color',
        'is_enabled',
        'last_synced_at',
        'last_sync_status',
        'last_sync_error',
        'sync_interval_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
            'sync_interval_minutes' => 'integer',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(IcsFeedEvent::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(IcsFeedRule::class)->orderBy('position');
    }

    public function needsSync(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if (! $this->last_synced_at) {
            return true;
        }

        return $this->last_synced_at->addMinutes($this->sync_interval_minutes)->isPast();
    }
}
