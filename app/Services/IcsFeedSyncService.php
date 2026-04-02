<?php

namespace App\Services;

use App\Models\IcsFeed;
use App\Models\IcsFeedEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Sabre\VObject\Reader;

class IcsFeedSyncService
{
    public function syncFeed(IcsFeed $feed): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'deleted' => 0];

        try {
            $response = Http::timeout(30)->get($feed->url);

            if (! $response->successful()) {
                $feed->update([
                    'last_synced_at' => now(),
                    'last_sync_status' => 'error',
                    'last_sync_error' => "HTTP {$response->status()}",
                ]);
                return $stats;
            }

            $vcalendar = Reader::read($response->body());
            $seenUids = [];

            // Pre-load existing UIDs to avoid N+1 queries
            $existingByUid = $feed->events()->pluck('id', 'uid');

            // Collect all event data first
            $upsertRows = [];

            foreach ($vcalendar->VEVENT ?? [] as $vevent) {
                $uid = (string) ($vevent->UID ?? '');
                if (! $uid) {
                    continue;
                }

                $seenUids[] = $uid;

                $startsAt = $vevent->DTSTART ? $this->parseDateTime($vevent->DTSTART) : null;
                if (! $startsAt) {
                    continue;
                }

                $endsAt = $vevent->DTEND ? $this->parseDateTime($vevent->DTEND) : null;
                $isAllDay = $vevent->DTSTART && ! $vevent->DTSTART->hasTime();

                $isNew = ! $existingByUid->has($uid);

                $upsertRows[] = [
                    'ics_feed_id' => $feed->id,
                    'uid' => $uid,
                    'title' => (string) ($vevent->SUMMARY ?? 'Untitled'),
                    'description' => (string) ($vevent->DESCRIPTION ?? null),
                    'location' => (string) ($vevent->LOCATION ?? null),
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'is_all_day' => $isAllDay,
                    'recurrence_rule' => $vevent->RRULE ? (string) $vevent->RRULE : null,
                    'raw_vevent' => $vevent->serialize(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($isNew) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            }

            // Bulk upsert — insert or update on unique(ics_feed_id, uid)
            if (! empty($upsertRows)) {
                foreach (array_chunk($upsertRows, 100) as $chunk) {
                    IcsFeedEvent::upsert(
                        $chunk,
                        ['ics_feed_id', 'uid'],
                        ['title', 'description', 'location', 'starts_at', 'ends_at', 'is_all_day', 'recurrence_rule', 'raw_vevent', 'updated_at']
                    );
                }
            }

            // Remove events no longer in the feed
            if (! empty($seenUids)) {
                $stats['deleted'] = $feed->events()
                    ->whereNotIn('uid', $seenUids)
                    ->delete();
            }

            // Apply rules to all events in this feed
            $this->applyRules($feed);

            $feed->update([
                'last_synced_at' => now(),
                'last_sync_status' => 'success',
                'last_sync_error' => null,
            ]);
        } catch (\Exception $e) {
            $feed->update([
                'last_synced_at' => now(),
                'last_sync_status' => 'error',
                'last_sync_error' => substr($e->getMessage(), 0, 500),
            ]);
        }

        return $stats;
    }

    public function applyRules(IcsFeed $feed): int
    {
        $rules = $feed->rules()->where('is_enabled', true)->orderBy('position')->get();
        $events = $feed->events;
        $affected = 0;

        foreach ($events as $event) {
            // Reset flags before re-applying
            $event->is_relevant = false;
            $event->is_backgrounded = false;
            $event->relevance_note = null;

            foreach ($rules as $rule) {
                if ($rule->matches($event)) {
                    $rule->apply($event);
                }
            }

            if ($event->isDirty()) {
                $event->save();
                $affected++;
            }
        }

        return $affected;
    }

    private function parseDateTime($dtProp): ?Carbon
    {
        try {
            $dt = $dtProp->getDateTime();
            return Carbon::instance($dt);
        } catch (\Exception) {
            return null;
        }
    }
}
