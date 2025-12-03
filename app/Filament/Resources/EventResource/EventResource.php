<?php

namespace App\Filament\Resources\EventResource;

use App\Models\Event;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Calendar';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 60;
    
    public static function shouldRegisterNavigation(): bool
    {
        // Only show in navigation for super_admin role
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Event Details')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        DateTimePicker::make('start')
                            ->label('Start Date & Time')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('M d, Y H:i')
                            ->default(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Auto-set end time to 1 hour after start if end is not set
                                if ($state && !$get('end')) {
                                    $set('end', \Carbon\Carbon::parse($state)->addHour());
                                }
                            })
                            ->columnSpan(1),

                        DateTimePicker::make('end')
                            ->label('End Date & Time')
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('M d, Y H:i')
                            ->after('start')
                            ->default(now()->addHour())
                            ->columnSpan(1),

                        Toggle::make('all_day')
                            ->label('All Day Event')
                            ->default(false)
                            ->columnSpanFull(),

                        Select::make('event_type')
                            ->label('Event Type')
                            ->options(Event::getEventTypes())
                            ->required()
                            ->default('other')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $colors = Event::getEventColors();
                                $set('color', $colors[$state] ?? '#6b7280');
                            })
                            ->columnSpan(1),

                        ColorPicker::make('color')
                            ->label('Color')
                            ->columnSpan(1),

                        Toggle::make('is_completed')
                            ->label('Mark as Completed')
                            ->default(false)
                            ->columnSpanFull(),

                        Hidden::make('user_id')
                            ->default(Auth::id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Event::getEventTypes()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'payment' => 'danger',
                        'shipment' => 'info',
                        'document' => 'warning',
                        'meeting' => 'purple',
                        'deadline' => 'danger',
                        'reminder' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('start')
                    ->label('Start')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end')
                    ->label('End')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('all_day')
                    ->label('All Day')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_completed')
                    ->label('Completed')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_automatic')
                    ->label('Automatic')
                    ->boolean()
                    ->toggleable()
                    ->tooltip('Created automatically by the system'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Type')
                    ->options(Event::getEventTypes()),

                Tables\Filters\TernaryFilter::make('is_completed')
                    ->label('Completed')
                    ->placeholder('All events')
                    ->trueLabel('Only completed')
                    ->falseLabel('Only pending'),

                Tables\Filters\TernaryFilter::make('is_automatic')
                    ->label('Source')
                    ->placeholder('All events')
                    ->trueLabel('Only automatic')
                    ->falseLabel('Only manual'),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming')
                    ->query(fn (Builder $query): Builder => $query->where('start', '>=', now())),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query): Builder => $query->where('start', '<', now())->where('is_completed', false)),
            ])
            ->actions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Event $record): bool => !$record->is_completed)
                    ->action(fn (Event $record) => $record->markAsCompleted())
                    ->requiresConfirmation(),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Show only user's own events unless user can see all
        $user = Auth::user();
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists() || $user->hasRole('super_admin');

        if (!$canSeeAll) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
