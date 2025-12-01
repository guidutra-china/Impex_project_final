<x-filament-widgets::widget>
    <x-filament::section>
        <div class="calendar-container">
            <div id="calendar" wire:ignore></div>
        </div>
    </x-filament::section>

    @push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            var events = @js($events);
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
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
                    // Show event details in modal or redirect to edit
                    var event = info.event;
                    var props = event.extendedProps;
                    
                    var message = event.title;
                    if (props.description) {
                        message += '\n\n' + props.description;
                    }
                    message += '\n\nType: ' + props.type;
                    message += '\nCompleted: ' + (props.completed ? 'Yes' : 'No');
                    
                    if (confirm(message + '\n\nEdit this event?')) {
                        window.location.href = '/admin/events/' + event.id + '/edit';
                    }
                },
                eventDidMount: function(info) {
                    // Add tooltip
                    if (info.event.extendedProps.description) {
                        info.el.setAttribute('title', info.event.extendedProps.description);
                    }
                    
                    // Add strikethrough for completed events
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
                firstDay: 0, // Sunday
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
            
            // Refresh calendar when Livewire updates
            Livewire.on('refreshCalendar', () => {
                calendar.refetchEvents();
            });
        });
    </script>
    @endpush

    @push('styles')
    <style>
        .calendar-container {
            padding: 1rem;
        }
        
        #calendar {
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
    @endpush
</x-filament-widgets::widget>
