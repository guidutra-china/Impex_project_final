<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DashboardConfigurationResource\Pages;
use App\Models\DashboardConfiguration;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use BackedEnum;
use FilamentSupportEnumsVerticalAlignment;

class DashboardConfigurationResource extends Resource
{
    protected static ?string $model = DashboardConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|BackedEnum|null $navigationGroup = 'Configurações';

    protected static ?string $label = 'Configuração do Dashboard';

    protected static ?string $pluralLabel = 'Configurações do Dashboard';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Widgets Visíveis')
                    ->description('Selecione quais widgets deseja visualizar')
                    ->schema([
                        Forms\Components\CheckboxList::make('visible_widgets')
                            ->label('Widgets')
                            ->options(function () {
                                $widgets = \App\Models\AvailableWidget::where('is_available', true)
                                    ->get()
                                    ->mapWithKeys(fn($w) => [$w->widget_id => $w->title])
                                    ->toArray();

                                return $widgets;
                            })
                            ->required(),
                    ]),

                Forms\Components\Section::make('Ordem dos Widgets')
                    ->description('Arraste para reordenar os widgets')
                    ->schema([
                        Forms\Components\Textarea::make('widget_order')
                            ->label('Ordem (JSON)')
                            ->rows(5)
                            ->disabled()
                            ->hint('Use a interface de personalização para reordenar'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('visible_widgets')
                    ->label('Widgets Visíveis')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            return 'N/A';
                        }

                        return count($state) . ' widgets';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reset')
                    ->label('Resetar para Padrão')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (DashboardConfiguration $record) {
                        $service = app(\App\Services\DashboardConfigurationService::class);
                        $service->resetToDefault($record->user);

                        \Filament\Notifications\Notification::make()
                            ->title('Sucesso')
                            ->body('Configuração resetada para o padrão')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDashboardConfigurations::route('/'),
            'edit' => Pages\EditDashboardConfiguration::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
