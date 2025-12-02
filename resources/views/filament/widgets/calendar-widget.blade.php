<div class="col-span-full">
    <x-filament-widgets::widget class="col-span-full">
        <x-filament::section>
            <x-slot name="heading">
                Calendar
            </x-slot>
            
            <x-slot name="headerEnd">
                <div>
                    @foreach ($this->getCachedHeaderActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </x-slot>
            <div style="padding: 1rem; width: 100%;">
                <div id="calendar-widget" style="min-height: 600px;"></div>
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for FullCalendar to load
        function initCalendar() {
            if (typeof FullCalendar === 'undefined') {
                console.log('Waiting for FullCalendar...');
                setTimeout(initCalendar, 100);
                return;
            }
            
            var calendarEl = document.getElementById('calendar-widget');
            if (!calendarEl) {
                console.error('Calendar element not found');
                return;
            }
            
            var events = {!! json_encode($events) !!};
            console.log('Events loaded:', events);
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: 'Today',
                    month: 'Month',
                    week: 'Week',
                    day: 'Day',
                    list: 'List'
                },
                locale: 'en',
                events: events,
                eventClick: function(info) {
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
            
            // Listen for event creation to refresh calendar
            window.addEventListener('eventCreated', function() {
                calendar.refetchEvents();
                location.reload(); // Reload to show new event
            });
        }
        
        initCalendar();
    });
    
    // Livewire event listener
    document.addEventListener('livewire:init', () => {
        Livewire.on('eventCreated', () => {
            setTimeout(() => location.reload(), 500);
        });
    });
    </script>

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
</div>
