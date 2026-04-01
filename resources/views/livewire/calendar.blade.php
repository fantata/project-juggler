<x-slot name="header">
    <h2 class="font-semibold text-xl text-bark-800 dark:text-cream-200 leading-tight">Calendar</h2>
</x-slot>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-end mb-4">
        <button
            wire:click="openNewEvent"
            class="inline-flex items-center gap-2 px-4 py-2 bg-terracotta-500 text-white text-sm font-medium rounded-lg hover:bg-terracotta-600 transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Event
        </button>
    </div>
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Calendar -->
        <div class="flex-1 min-w-0">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700 p-4"
                x-data="calendarWidget()"
                x-init="initCalendar()"
                wire:ignore
            >
                <div id="fullcalendar" class="fc-warm"></div>
            </div>
        </div>

        <!-- Sidebar: Layers -->
        <div class="w-full lg:w-64 shrink-0 space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-bark-700 dark:text-cream-200 mb-3">Layers</h3>

                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <span class="w-3 h-3 rounded-full bg-terracotta-500 shrink-0"></span>
                        <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">My Events</span>
                        <span class="text-xs text-gray-400">always on</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="showDeadlines" class="rounded border-gray-300 dark:border-gray-600 text-bark-500 focus:ring-bark-400 dark:bg-gray-700">
                        <span class="w-3 h-3 rounded-full bg-bark-500 shrink-0"></span>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Deadlines</span>
                    </label>

                    @foreach($feeds as $feed)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                value="{{ $feed->id }}"
                                wire:model.live="enabledFeeds"
                                class="rounded border-gray-300 dark:border-gray-600 text-moss-500 focus:ring-moss-400 dark:bg-gray-700"
                            >
                            <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $feed->color ?? '#9CA3AF' }}"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $feed->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Upcoming -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-bark-700 dark:text-cream-200 mb-3">Coming Up</h3>
                <div id="upcoming-events" class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <p class="text-xs text-gray-400 italic">Events load with calendar</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Form Modal -->
@if($showEventForm)
    <div class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50 p-4" wire:click.self="$set('showEventForm', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-cream-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-bark-800 dark:text-cream-200">
                    {{ $editingEventId ? 'Edit Event' : 'New Event' }}
                </h3>
                <button wire:click="$set('showEventForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveEvent" class="p-6 space-y-4">
                <div>
                    <label for="eventTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input type="text" wire:model="eventTitle" id="eventTitle" autofocus class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                    @error('eventTitle') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="eventStartsAt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start</label>
                        <input type="datetime-local" wire:model="eventStartsAt" id="eventStartsAt" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                    </div>
                    <div>
                        <label for="eventEndsAt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End</label>
                        <input type="datetime-local" wire:model="eventEndsAt" id="eventEndsAt" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="eventLocation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                    <input type="text" wire:model="eventLocation" id="eventLocation" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm" placeholder="Optional">
                </div>

                <div>
                    <label for="eventDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea wire:model="eventDescription" id="eventDescription" rows="2" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm" placeholder="Optional"></textarea>
                </div>

                <div>
                    <label for="eventRecurrenceRule" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Repeat</label>
                    <select wire:model="eventRecurrenceRule" id="eventRecurrenceRule" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                        <option value="">No repeat</option>
                        <option value="FREQ=DAILY">Daily</option>
                        <option value="FREQ=WEEKLY">Weekly</option>
                        <option value="FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR">Weekdays</option>
                        <option value="FREQ=MONTHLY">Monthly</option>
                        <option value="FREQ=YEARLY">Yearly</option>
                    </select>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="eventIsAllDay" class="rounded border-gray-300 dark:border-gray-600 text-terracotta-500 focus:ring-terracotta-400 dark:bg-gray-700">
                    <span class="text-sm text-gray-700 dark:text-gray-300">All-day event</span>
                </label>

                <div class="flex items-center justify-between pt-4 border-t border-cream-200 dark:border-gray-700">
                    @if($editingEventId)
                        <button type="button" wire:click="deleteEvent" wire:confirm="Delete this event?" class="text-sm text-red-500 hover:text-red-700">
                            Delete event
                        </button>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-terracotta-500 rounded-lg hover:bg-terracotta-600 transition-colors">
                        {{ $editingEventId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

@script
<script>
    import { Calendar } from '@fullcalendar/core';
    import dayGridPlugin from '@fullcalendar/daygrid';
    import timeGridPlugin from '@fullcalendar/timegrid';
    import listPlugin from '@fullcalendar/list';
    import interactionPlugin from '@fullcalendar/interaction';

    Alpine.data('calendarWidget', () => ({
        calendar: null,

        initCalendar() {
            const el = document.getElementById('fullcalendar');

            this.calendar = new Calendar(el, {
                plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
                initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek',
                },
                height: 'auto',
                nowIndicator: true,
                editable: false,
                selectable: true,
                dayMaxEvents: 3,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                },

                events: (info, successCallback, failureCallback) => {
                    $wire.fetchEvents(info.startStr, info.endStr).then(events => {
                        successCallback(events);
                    }).catch(failureCallback);
                },

                dateClick: (info) => {
                    $wire.openNewEvent(info.dateStr + 'T09:00');
                },

                eventClick: (info) => {
                    const props = info.event.extendedProps;
                    if (props.type === 'native') {
                        $wire.editEvent(props.eventId);
                    } else if (props.type === 'deadline') {
                        window.location.href = '/projects/' + props.projectId;
                    }
                },
            });

            this.calendar.render();

            Livewire.on('calendar-refresh', () => {
                this.calendar.refetchEvents();
            });

            // Refetch when layer toggles change
            $wire.$watch('showDeadlines', () => this.calendar.refetchEvents());
            $wire.$watch('enabledFeeds', () => this.calendar.refetchEvents());
        }
    }));
</script>
@endscript
