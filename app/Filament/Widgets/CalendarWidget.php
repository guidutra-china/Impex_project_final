<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class CalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.calendar-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;
    
    public static function canView(): bool
    {
        // Temporarily disabled for testing - enable after creating permission
        return auth()->check();
        
        // TODO: Uncomment after creating 'View:CalendarWidget' permission
        // return auth()->check() && auth()->user()->can('View:CalendarWidget');
    }

    protected function getEvents(): array
    {
        $user = Auth::user();
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists() || $user->hasRole('super_admin');

        $query = Event::query();

        if (!$canSeeAll) {
            $query->where('user_id', $user->id);
        }

        return $query->get()->map(function (Event $event) {
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
