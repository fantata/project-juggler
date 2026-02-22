<?php

namespace App\Console\Commands;

use App\Models\ExternalCalendarEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Sabre\VObject;

class SyncExternalCalendars extends Command
{
    protected $signature = 'calendars:sync-external';
    protected $description = 'Sync external calendar ICS feeds into the local cache';

    public function handle(): int
    {
        $feeds = config('calendars.external_feeds', []);

        if (empty(array_filter($feeds))) {
            $this->info('No external calendar feeds configured.');
            return 0;
        }

        foreach ($feeds as $name => $url) {
            if (empty($url)) {
                continue;
            }

            $this->info("Syncing '{$name}' from {$url}...");

            try {
                $response = Http::timeout(30)->get($url);

                if (!$response->successful()) {
                    $this->error("Failed to fetch '{$name}': HTTP {$response->status()}");
                    continue;
                }

                $icsData = $response->body();
                $vcalendar = VObject\Reader::read($icsData);

                $synced = 0;

                if (isset($vcalendar->VEVENT)) {
                    foreach ($vcalendar->VEVENT as $event) {
                        $uid = (string) $event->UID;
                        if (empty($uid)) {
                            continue;
                        }

                        $dtstart = $event->DTSTART?->getDateTime();
                        if (!$dtstart) {
                            continue;
                        }

                        $dtend = $event->DTEND?->getDateTime();
                        $allDay = !$event->DTSTART->hasTime();

                        ExternalCalendarEvent::updateOrCreate(
                            ['source' => $name, 'uid' => $uid],
                            [
                                'title' => (string) ($event->SUMMARY ?? 'Untitled'),
                                'start' => $dtstart,
                                'end' => $dtend,
                                'location' => (string) ($event->LOCATION ?? null) ?: null,
                                'all_day' => $allDay,
                            ]
                        );
                        $synced++;
                    }
                }

                $this->info("  Synced {$synced} events for '{$name}'.");
            } catch (\Exception $e) {
                $this->error("Error syncing '{$name}': {$e->getMessage()}");
            }
        }

        return 0;
    }
}
