<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Repositories\EventRepository;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class CalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.calendar-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;
    
    // Disable lazy loading to avoid Livewire issues
    protected static bool $isLazy = false;

    protected EventRepository $repository;

    public function mount(): void
    {
        $this->repository = app(EventRepository::class);
    }
    
    public static function canView(): bool
    {
        // Temporarily disabled for testing - enable after creating permission
        return auth()->check();
        
        // TODO: Uncomment after creating 'View:CalendarWidget' permission
        // return auth()->user()->can('View:CalendarWidget');
    }

    public function getViewData(): array
    {
        return [
            'events' => $this->getEvents(),
            'eventTypes' => Event::getEventTypes(),
            'eventColors' => Event::getEventColors(),
        ];
    }

    protected function getEvents(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists() || $user->hasRole('super_admin');

        // Get events using repository
        if ($canSeeAll) {
            $events = $this->repository->getRecent(1000);
        } else {
            $events = $this->repository->getModel()
                ->where('user_id', $user->id)
                ->get();
        }

        return $events->map(function (Event $event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start->toIso8601String(),
                'end' => $event->end?->toIso8601String(),
                'allDay' => $event->all_day,
                'backgroundColor' => $event->color ?? $event->getDefaultColor(),
                'borderColor' => $event->color ?? $event->getDefaultColor(),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'description' => $event->description,
                    'type' => $event->event_type,
                    'completed' => $event->is_completed,
                    'automatic' => $event->is_automatic,
                ],
            ];
        })->toArray();
    }
}
