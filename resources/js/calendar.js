// FullCalendar widget, bundled through Vite and registered as an Alpine
// component. It used to live in an inline @script with ES imports, which
// Livewire evaluates via Function() — that throws "Cannot use import
// statement outside a module" and broke the whole page's JS. Bundling here
// fixes that (and the calendar actually renders now).
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('calendarWidget', () => ({
        calendar: null,

        initCalendar() {
            const el = this.$el.querySelector('#fullcalendar');

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
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },

                events: (info, successCallback, failureCallback) => {
                    this.$wire.fetchEvents(info.startStr, info.endStr)
                        .then(successCallback)
                        .catch(failureCallback);
                },

                dateClick: (info) => {
                    this.$wire.openNewEvent(info.dateStr + 'T09:00');
                },

                eventClick: (info) => {
                    const props = info.event.extendedProps;
                    if (props.type === 'native') {
                        this.$wire.editEvent(props.eventId);
                    } else if (props.type === 'deadline') {
                        window.location.href = '/projects/' + props.projectId;
                    }
                },
            });

            this.calendar.render();

            window.Livewire.on('calendar-refresh', () => this.calendar.refetchEvents());
            this.$wire.$watch('showDeadlines', () => this.calendar.refetchEvents());
            this.$wire.$watch('enabledFeeds', () => this.calendar.refetchEvents());
        },
    }));
});
