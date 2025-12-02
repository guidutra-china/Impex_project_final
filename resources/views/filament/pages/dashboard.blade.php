<x-filament-panels::page>
    {{-- Custom Dashboard Layout --}}
    
    {{-- Calendar Widget - Full Width at Top --}}
    <div class="grid grid-cols-1 gap-6 mb-6">
        @foreach ($this->getVisibleWidgets() as $widget)
            @if (is_string($widget) && $widget === \App\Filament\Widgets\CalendarWidget::class)
                @livewire($widget)
            @endif
        @endforeach
    </div>

    {{-- Stats Widgets - 3 Columns Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($this->getVisibleWidgets() as $widget)
            @if (is_string($widget) && $widget !== \App\Filament\Widgets\CalendarWidget::class)
                @livewire($widget)
            @endif
        @endforeach
    </div>
</x-filament-panels::page>
