<div class="col-span-full">
    <x-filament-widgets::widget class="col-span-full">
        <x-filament::section>
            <div style="padding: 1rem; width: 100%;">
                {{-- Event Type Filters - Dynamically generated from Model --}}
                <div id="event-filters" style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f9fafb; border-radius: 0.5rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
                    <span style="font-weight: 600; font-size: 0.875rem; color: #374151;">Filter by type:</span>
                    @foreach($eventTypes as $typeKey => $typeName)
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem;">
                            <input type="checkbox" class="event-type-filter" data-type="{{ $typeKey }}" checked style="cursor: pointer;">
                            <span style="color: {{ $eventColors[$typeKey] ?? '#6b7280' }};">●</span> {{ $typeName }}
                        </label>
                    @endforeach
                </div>
                
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
            
            // Add custom button to toolbar
            const customButton = document.createElement('a');
            customButton.href = '{{ route('filament.admin.resources.events.create') }}';
            customButton.className = 'fc-button fc-button-primary';
            customButton.style.cssText = 'margin-left: 0.5rem; padding: 0.4em 0.65em; font-size: 1em; line-height: 1.5; border-radius: 0.25em; cursor: pointer; background-color: rgb(99 102 241); border-color: rgb(99 102 241); color: white; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;';
            customButton.innerHTML = '<svg style="width: 1em; height: 1em;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>';
            customButton.title = 'New Event';
            
            // Store all events for filtering
            let allEvents = events;
            // Initialize with all event types from Model
            let activeFilters = new Set({!! json_encode(array_keys($eventTypes)) !!});
            
            // Function to get filtered events
            function getFilteredEvents() {
                return allEvents.filter(event => activeFilters.has(event.extendedProps.type));
            }
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
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
                events: getFilteredEvents(),
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
                        window.location.href = '/panel/events/' + event.id + '/edit';
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
            
            // Insert custom button next to Today button
            const todayButton = calendarEl.querySelector('.fc-today-button');
            if (todayButton) {
                todayButton.parentNode.insertBefore(customButton, todayButton.nextSibling);
            }
            
            // Setup filter event listeners
            document.querySelectorAll('.event-type-filter').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const type = this.dataset.type;
                    if (this.checked) {
                        activeFilters.add(type);
                    } else {
                        activeFilters.delete(type);
                    }
                    
                    // Update calendar events
                    calendar.removeAllEvents();
                    calendar.addEventSource(getFilteredEvents());
                });
            });
            
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
