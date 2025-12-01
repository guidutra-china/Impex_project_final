<x-filament-widgets::widget>
    <x-filament::section>
        <div class="calendar-container" style="padding: 1rem;">
            <div id="calendar-{{ $this->getId() }}" wire:ignore style="min-height: 600px;"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<style>
    .calendar-container {
        padding: 1rem;
    }
    
    #calendar-{{ $this->getId() }} {
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* FullCalendar theme adjustments for Filament */
    .fc {
        font-family: inherit;
    }
    
    .fc-button {
        background-color: rgb(99 102 241) !important;
        border-color: rgb(99 102 241) !important;
        color: white !important;
        text-transform: capitalize;
    }
    
    .fc-button:hover {
        background-color: rgb(79 70 229) !important;
        border-color: rgb(79 70 229) !important;
    }
    
    .fc-button-active {
        background-color: rgb(67 56 202) !important;
        border-color: rgb(67 56 202) !important;
    }
    
    .fc-daygrid-day-number {
        color: inherit;
    }
    
    .fc-col-header-cell-cushion {
        color: inherit;
    }
    
    .fc-event {
        cursor: pointer;
        border-radius: 4px;
        padding: 2px 4px;
    }
    
    .fc-event:hover {
        opacity: 0.8;
    }
    
    /* Dark mode support */
    .dark .fc {
        color: rgb(229 231 235);
    }
    
    .dark .fc-theme-standard td,
    .dark .fc-theme-standard th {
        border-color: rgb(55 65 81);
    }
    
    .dark .fc-theme-standard .fc-scrollgrid {
        border-color: rgb(55 65 81);
    }
    
    .dark .fc-daygrid-day-number {
        color: rgb(229 231 235);
    }
    
    .dark .fc-col-header-cell-cushion {
        color: rgb(229 231 235);
    }
</style>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
(function() {
    const calendarId = 'calendar-{{ $this->getId() }}';
    const calendarEl = document.getElementById(calendarId);
    
    if (!calendarEl) {
        console.error('Calendar element not found:', calendarId);
        return;
    }
    
    const events = @js($events);
    console.log('Calendar Widget - Events loaded:', events);
    console.log('Calendar Widget - Element found:', calendarEl);
    
    if (typeof FullCalendar === 'undefined') {
        console.error('FullCalendar library not loaded');
        return;
    }
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia',
            list: 'Lista'
        },
        locale: 'pt-br',
        events: events,
        eventClick: function(info) {
            const event = info.event;
            const props = event.extendedProps;
            
            let message = event.title;
            if (props.description) {
                message += '\n\n' + props.description;
            }
            message += '\n\nTipo: ' + props.type;
            message += '\nConcluído: ' + (props.completed ? 'Sim' : 'Não');
            
            if (confirm(message + '\n\nEditar este evento?')) {
                window.location.href = '/admin/events/' + event.id + '/edit';
            }
        },
        eventDidMount: function(info) {
            if (info.event.extendedProps.description) {
                info.el.setAttribute('title', info.event.extendedProps.description);
            }
            
            if (info.event.extendedProps.completed) {
                info.el.style.textDecoration = 'line-through';
                info.el.style.opacity = '0.6';
            }
        },
        height: 'auto',
        aspectRatio: 1.8,
        navLinks: true,
        editable: false,
        dayMaxEvents: true,
        nowIndicator: true,
        weekNumbers: false,
        firstDay: 0,
        displayEventTime: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false
        },
        slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false
        }
    });
    
    calendar.render();
    console.log('Calendar Widget - Rendered successfully');
    
    // Refresh calendar when Livewire updates
    if (typeof Livewire !== 'undefined') {
        Livewire.on('refreshCalendar', () => {
            calendar.refetchEvents();
        });
    }
})();
</script>
