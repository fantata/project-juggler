<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcsFeedEvent extends Model
{
    protected $fillable = [
        'ics_feed_id',
        'uid',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'is_all_day',
        'recurrence_rule',
        'raw_vevent',
        'is_backgrounded',
        'is_relevant',
        'relevance_note',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
            'is_backgrounded' => 'boolean',
            'is_relevant' => 'boolean',
        ];
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(IcsFeed::class, 'ics_feed_id');
    }

    public function scopeInRange($query, \Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        return $query->where('starts_at', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->where('ends_at', '>=', $from)
                    ->orWhereNull('ends_at');
            });
    }
}
