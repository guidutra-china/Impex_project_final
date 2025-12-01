<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Calendário';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Detalhes do Evento')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),

                        DateTimePicker::make('start')
                            ->label('Data e Hora de Início')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('M d, Y H:i')
                            ->columnSpan(1),

                        DateTimePicker::make('end')
                            ->label('Data e Hora de Término')
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('M d, Y H:i')
                            ->after('start')
                            ->columnSpan(1),

                        Toggle::make('all_day')
                            ->label('Evento de Dia Inteiro')
                            ->default(false)
                            ->columnSpanFull(),

                        Select::make('event_type')
                            ->label('Tipo de Evento')
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
                            ->label('Cor')
                            ->columnSpan(1),

                        Toggle::make('is_completed')
                            ->label('Marcar como Concluído')
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
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('event_type')
                    ->label('Tipo')
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
                    ->label('Início')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end')
                    ->label('Término')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('all_day')
                    ->label('Dia Inteiro')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_completed')
                    ->label('Concluído')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_automatic')
                    ->label('Automático')
                    ->boolean()
                    ->toggleable()
                    ->tooltip('Criado automaticamente pelo sistema'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Proprietário')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Tipo')
                    ->options(Event::getEventTypes()),

                Tables\Filters\TernaryFilter::make('is_completed')
                    ->label('Concluído')
                    ->placeholder('Todos os eventos')
                    ->trueLabel('Apenas concluídos')
                    ->falseLabel('Apenas pendentes'),

                Tables\Filters\TernaryFilter::make('is_automatic')
                    ->label('Origem')
                    ->placeholder('Todos os eventos')
                    ->trueLabel('Apenas automáticos')
                    ->falseLabel('Apenas manuais'),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Próximos')
                    ->query(fn (Builder $query): Builder => $query->where('start', '>=', now())),

                Tables\Filters\Filter::make('overdue')
                    ->label('Atrasados')
                    ->query(fn (Builder $query): Builder => $query->where('start', '<', now())->where('is_completed', false)),
            ])
            ->actions([
                Action::make('complete')
                    ->label('Concluir')
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
