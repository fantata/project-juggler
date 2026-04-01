<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Recur\RRuleIterator;

class CalendarEvent extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'is_all_day',
        'recurrence_rule',
        'recurrence_until',
        'recurrence_parent_id',
        'color',
        'uid',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
            'recurrence_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CalendarEvent $event) {
            if (empty($event->uid)) {
                $event->uid = Str::uuid() . '@project-juggler';
            }
        });
    }

    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'recurrence_parent_id');
    }

    public function recurrenceExceptions(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'recurrence_parent_id');
    }

    /**
     * Get all occurrences of this event within a date range.
     * For non-recurring events, returns the event itself if it falls in range.
     * For recurring events, expands the RRULE and returns occurrence datetimes.
     */
    public function occurrencesInRange(\Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        if (! $this->recurrence_rule) {
            if ($this->starts_at->lte($to) && ($this->ends_at ?? $this->starts_at)->gte($from)) {
                return [$this->starts_at];
            }
            return [];
        }

        $occurrences = [];
        $rrule = new RRuleIterator($this->recurrence_rule, $this->starts_at->toDateTime());
        $duration = $this->ends_at ? $this->starts_at->diffInSeconds($this->ends_at) : 0;

        while ($rrule->valid()) {
            $date = \Carbon\Carbon::instance($rrule->current());

            if ($date->gt($to)) {
                break;
            }

            if ($this->recurrence_until && $date->gt($this->recurrence_until)) {
                break;
            }

            $endDate = $duration > 0 ? $date->copy()->addSeconds($duration) : $date;

            if ($endDate->gte($from)) {
                $occurrences[] = $date;
            }

            $rrule->next();

            // Safety limit for runaway recurrence
            if (count($occurrences) > 365) {
                break;
            }
        }

        return $occurrences;
    }

    /**
     * Convert to a VEvent for ICS export.
     */
    public function toVEvent(VCalendar $vcalendar): \Sabre\VObject\Component\VEvent
    {
        $vevent = $vcalendar->add('VEVENT', [
            'UID' => $this->uid,
            'SUMMARY' => $this->title,
            'DTSTART' => $this->starts_at->toDateTime(),
        ]);

        if ($this->ends_at) {
            $vevent->add('DTEND', $this->ends_at->toDateTime());
        }

        if ($this->description) {
            $vevent->add('DESCRIPTION', $this->description);
        }

        if ($this->location) {
            $vevent->add('LOCATION', $this->location);
        }

        if ($this->recurrence_rule) {
            $vevent->add('RRULE', $this->recurrence_rule);
        }

        $vevent->add('DTSTAMP', now()->toDateTime());

        return $vevent;
    }

    /**
     * Scope: events that fall within a date range (non-recurring only).
     * For recurring events, use occurrencesInRange() on each event.
     */
    public function scopeInRange($query, \Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        return $query->where(function ($q) use ($from, $to) {
            // Non-recurring events in range
            $q->where(function ($q2) use ($from, $to) {
                $q2->whereNull('recurrence_rule')
                    ->where('starts_at', '<=', $to)
                    ->where(function ($q3) use ($from) {
                        $q3->where('ends_at', '>=', $from)
                            ->orWhereNull('ends_at');
                    });
            })
            // Recurring events that START before the range end
            ->orWhere(function ($q2) use ($to) {
                $q2->whereNotNull('recurrence_rule')
                    ->where('starts_at', '<=', $to);
            });
        });
    }
}
