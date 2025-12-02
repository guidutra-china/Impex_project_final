@php
    $calendarId = 'calendar-' . $this->getId();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div 
            x-data="{ 
                calendar: null,
                events: @js($events),
                calendarId: '{{ $calendarId }}'
            }"
            x-init="
                console.log('Alpine init - Calendar ID:', calendarId);
                console.log('Alpine init - Events:', events);
                
                $nextTick(() => {
                    const calendarEl = document.getElementById(calendarId);
                    console.log('Alpine init - Element:', calendarEl);
                    
                    if (!calendarEl) {
                        console.error('Calendar element not found!');
                        return;
                    }
                    
                    if (typeof FullCalendar === 'undefined') {
                        console.error('FullCalendar not loaded!');
                        return;
                    }
                    
                    calendar = new FullCalendar.Calendar(calendarEl, {
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
                                message += '\\n\\n' + props.description;
                            }
                            message += '\\n\\nTipo: ' + props.type;
                            message += '\\nConcluído: ' + (props.completed ? 'Sim' : 'Não');
                            
                            if (confirm(message + '\\n\\nEditar este evento?')) {
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
                    console.log('Calendar rendered successfully!');
                });
            "
            style="padding: 1rem;"
        >
            <div id="{{ $calendarId }}" wire:ignore style="min-height: 600px;"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<style>
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
