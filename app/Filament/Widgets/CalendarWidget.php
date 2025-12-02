<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\Widget;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Support\Facades\Auth;

class CalendarWidget extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected string $view = 'filament.widgets.calendar-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;
    
    // Disable lazy loading to avoid Livewire issues
    protected static bool $isLazy = false;
    
    public static function canView(): bool
    {
        // Temporarily disabled for testing - enable after creating permission
        return auth()->check();
        
        // TODO: Uncomment after creating 'View:CalendarWidget' permission
        // return auth()->user()->can('View:CalendarWidget');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createEvent')
                ->label('New Event')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3),

                    DateTimePicker::make('start')
                        ->label('Start Date & Time')
                        ->required()
                        ->seconds(false)
                        ->native(false)
                        ->default(now())
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if ($state && !$get('end')) {
                                $set('end', \Carbon\Carbon::parse($state)->addHour());
                            }
                        }),

                    DateTimePicker::make('end')
                        ->label('End Date & Time')
                        ->seconds(false)
                        ->native(false)
                        ->after('start')
                        ->default(now()->addHour()),

                    Toggle::make('all_day')
                        ->label('All Day Event')
                        ->default(false),

                    Select::make('event_type')
                        ->label('Event Type')
                        ->options(Event::getEventTypes())
                        ->required()
                        ->default('other')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $colors = Event::getEventColors();
                            $set('color', $colors[$state] ?? '#6b7280');
                        }),

                    ColorPicker::make('color')
                        ->label('Color'),

                    Toggle::make('is_completed')
                        ->label('Mark as Completed')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $data['user_id'] = Auth::id();
                    $data['is_automatic'] = false;
                    
                    Event::create($data);
                    
                    $this->dispatch('eventCreated');
                })
                ->successNotificationTitle('Event created successfully!')
                ->modalWidth('2xl'),
        ];
    }

    public function getViewData(): array
    {
        return [
            'events' => $this->getEvents(),
        ];
    }

    protected function getEvents(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
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
