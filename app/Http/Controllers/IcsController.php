<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\User;
use Sabre\VObject\Component\VCalendar;

class IcsController extends Controller
{
    public function __invoke(string $token)
    {
        $user = User::where('ics_feed_token', hash('sha256', $token))->first();

        if (! $user) {
            abort(404);
        }

        $vcalendar = new VCalendar();
        $vcalendar->PRODID = '-//Project Juggler//EN';
        $vcalendar->{'X-WR-CALNAME'} = 'Project Juggler';

        // Only native events — never external feed events
        $events = CalendarEvent::whereNull('recurrence_parent_id')->get();

        foreach ($events as $event) {
            $event->toVEvent($vcalendar);
        }

        return response($vcalendar->serialize(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="project-juggler.ics"',
        ]);
    }
}
