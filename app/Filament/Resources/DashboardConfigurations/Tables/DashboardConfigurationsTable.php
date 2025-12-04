<?php

namespace App\Filament\Resources\DashboardConfigurations\Tables;

use App\Models\DashboardConfiguration;
use App\Services\DashboardConfigurationService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DashboardConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('visible_widgets')
                    ->label('Widgets Visíveis')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            return 'N/A';
                        }
                        return count($state) . ' widgets';
                    }),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                Action::make('reset')
                    ->label('Resetar para Padrão')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (DashboardConfiguration $record) {
                        $service = app(DashboardConfigurationService::class);
                        $service->resetToDefault($record->user);

                        Notification::make()
                            ->title('Sucesso')
                            ->body('Configuração resetada para o padrão')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
