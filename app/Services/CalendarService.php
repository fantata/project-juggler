<?php

namespace App\Services;

use Sabre\CalDAV\Backend\PDO as CalDAVBackend;
use Sabre\VObject;

class CalendarService
{
    private CalDAVBackend $backend;

    public function __construct()
    {
        $this->backend = new CalDAVBackend(\DB::connection()->getPdo());
    }

    /**
     * Get all calendars for a user principal.
     */
    public function getCalendars(string $principalUri): array
    {
        return $this->backend->getCalendarsForUser($principalUri);
    }

    /**
     * Get events in a date range from a specific calendar.
     */
    public function getEventsInRange(int $calendarId, \DateTime $start, \DateTime $end): array
    {
        $objects = $this->backend->getCalendarObjects([$calendarId, 1]);
        $events = [];

        foreach ($objects as $obj) {
            $data = $this->backend->getCalendarObject([$calendarId, 1], $obj['uri']);
            if (!$data || empty($data['calendardata'])) {
                continue;
            }

            try {
                $vcalendar = VObject\Reader::read($data['calendardata']);
            } catch (\Exception $e) {
                continue;
            }

            if (!isset($vcalendar->VEVENT)) {
                continue;
            }

            foreach ($vcalendar->VEVENT as $vevent) {
                $dtstart = $vevent->DTSTART?->getDateTime();
                if (!$dtstart) {
                    continue;
                }

                $dtend = $vevent->DTEND?->getDateTime() ?? $dtstart;

                // Check if event overlaps with the requested range
                if ($dtend >= $start && $dtstart <= $end) {
                    $event = [
                        'uri' => $obj['uri'],
                        'title' => (string) ($vevent->SUMMARY ?? 'Untitled'),
                        'start' => $dtstart,
                        'end' => $dtend,
                        'description' => (string) ($vevent->DESCRIPTION ?? ''),
                        'location' => (string) ($vevent->LOCATION ?? ''),
                        'all_day' => !$vevent->DTSTART->hasTime(),
                        'calendar_id' => $calendarId,
                        'source' => 'local',
                    ];

                    // Extract custom Project Juggler properties
                    if (isset($vevent->{'X-PJ-PROJECT-ID'})) {
                        $event['project_id'] = (int) (string) $vevent->{'X-PJ-PROJECT-ID'};
                    }
                    if (isset($vevent->{'X-PJ-ISSUE-ID'})) {
                        $event['issue_id'] = (int) (string) $vevent->{'X-PJ-ISSUE-ID'};
                    }
                    if (isset($vevent->{'X-PJ-ITEM-TYPE'})) {
                        $event['item_type'] = (string) $vevent->{'X-PJ-ITEM-TYPE'};
                    }

                    $events[] = $event;
                }
            }
        }

        usort($events, fn($a, $b) => $a['start'] <=> $b['start']);

        return $events;
    }

    /**
     * Get events from ALL calendars for a user in a date range.
     */
    public function getAllEventsInRange(string $principalUri, \DateTime $start, \DateTime $end): array
    {
        $calendars = $this->getCalendars($principalUri);
        $allEvents = [];

        foreach ($calendars as $calendar) {
            $calendarId = $calendar['id'][0] ?? $calendar['id'];
            $events = $this->getEventsInRange($calendarId, $start, $end);

            // Tag events with calendar info
            foreach ($events as &$event) {
                $event['calendar_name'] = $calendar['{DAV:}displayname'] ?? 'default';
                $event['calendar_color'] = $calendar['{http://apple.com/ns/ical/}calendar-color'] ?? null;
            }

            $allEvents = array_merge($allEvents, $events);
        }

        usort($allEvents, fn($a, $b) => $a['start'] <=> $b['start']);

        return $allEvents;
    }

    /**
     * Create an event on a calendar.
     */
    public function createEvent(int $calendarId, string $title, \DateTime $start, ?\DateTime $end = null, array $extra = []): string
    {
        $uid = \Str::uuid() . '@projectjuggler';
        $uri = $uid . '.ics';

        $vcalendar = new VObject\Component\VCalendar();
        $vcalendar->PRODID = '-//ProjectJuggler//EN';

        $vevent = $vcalendar->add('VEVENT', [
            'UID' => $uid,
            'DTSTAMP' => new \DateTime('now', new \DateTimeZone('UTC')),
            'DTSTART' => $start,
            'DTEND' => $end ?? $start,
            'SUMMARY' => $title,
        ]);

        if (!empty($extra['description'])) {
            $vevent->add('DESCRIPTION', $extra['description']);
        }
        if (!empty($extra['location'])) {
            $vevent->add('LOCATION', $extra['location']);
        }
        if (!empty($extra['project_id'])) {
            $vevent->add('X-PJ-PROJECT-ID', (string) $extra['project_id']);
        }
        if (!empty($extra['issue_id'])) {
            $vevent->add('X-PJ-ISSUE-ID', (string) $extra['issue_id']);
        }
        if (!empty($extra['item_type'])) {
            $vevent->add('X-PJ-ITEM-TYPE', $extra['item_type']);
        }

        $icalData = $vcalendar->serialize();
        $this->backend->createCalendarObject([$calendarId, 1], $uri, $icalData);

        return $uri;
    }

    /**
     * Update an event on a calendar.
     */
    public function updateEvent(int $calendarId, string $objectUri, array $changes): void
    {
        $data = $this->backend->getCalendarObject([$calendarId, 1], $objectUri);
        if (!$data) {
            return;
        }

        $vcalendar = VObject\Reader::read($data['calendardata']);
        $vevent = $vcalendar->VEVENT;

        if (isset($changes['title'])) {
            $vevent->SUMMARY = $changes['title'];
        }
        if (array_key_exists('description', $changes)) {
            if ($changes['description']) {
                $vevent->DESCRIPTION = $changes['description'];
            } elseif (isset($vevent->DESCRIPTION)) {
                unset($vevent->DESCRIPTION);
            }
        }
        if (isset($changes['start'])) {
            $vevent->DTSTART = $changes['start'];
        }
        if (isset($changes['end'])) {
            $vevent->DTEND = $changes['end'];
        }
        if (isset($changes['location'])) {
            $vevent->LOCATION = $changes['location'];
        }

        $this->backend->updateCalendarObject([$calendarId, 1], $objectUri, $vcalendar->serialize());
    }

    /**
     * Delete an event from a calendar.
     */
    public function deleteEvent(int $calendarId, string $objectUri): void
    {
        $this->backend->deleteCalendarObject([$calendarId, 1], $objectUri);
    }

    /**
     * Link an event to a project via custom iCal property.
     */
    public function linkToProject(int $calendarId, string $objectUri, int $projectId): void
    {
        $data = $this->backend->getCalendarObject([$calendarId, 1], $objectUri);
        if (!$data) {
            return;
        }

        $vcalendar = VObject\Reader::read($data['calendardata']);
        $vevent = $vcalendar->VEVENT;
        $vevent->add('X-PJ-PROJECT-ID', (string) $projectId);

        $this->backend->updateCalendarObject([$calendarId, 1], $objectUri, $vcalendar->serialize());
    }

    /**
     * Ensure a default calendar exists for a user principal.
     */
    public function ensureDefaultCalendar(string $principalUri, string $displayName = 'Default'): int
    {
        $calendars = $this->getCalendars($principalUri);

        foreach ($calendars as $calendar) {
            if (($calendar['uri'] ?? '') === 'default') {
                return $calendar['id'][0] ?? $calendar['id'];
            }
        }

        // Create a new default calendar
        return $this->backend->createCalendar($principalUri, 'default', [
            '{DAV:}displayname' => $displayName,
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Default calendar',
            '{http://apple.com/ns/ical/}calendar-color' => '#3B82F6',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT', 'VTODO']),
        ]);
    }

    /**
     * Create a named calendar for a user.
     */
    public function createCalendar(string $principalUri, string $uri, string $displayName, ?string $color = null): int
    {
        $props = [
            '{DAV:}displayname' => $displayName,
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT', 'VTODO']),
        ];

        if ($color) {
            $props['{http://apple.com/ns/ical/}calendar-color'] = $color;
        }

        return $this->backend->createCalendar($principalUri, $uri, $props);
    }
}
