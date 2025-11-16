<?php

namespace App\Filament\Resources\ExchangeRates\Tables;

use App\Models\ExchangeRate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExchangeRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('baseCurrency.code')
                    ->label('From')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('targetCurrency.code')
                    ->label('To')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('rate')
                    ->numeric(decimalPlaces: 6)
                    ->sortable(),

                TextColumn::make('inverse_rate')
                    ->label('Inverse')
                    ->numeric(decimalPlaces: 6)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                BadgeColumn::make('source')
                    ->colors([
                        'success' => 'api',
                        'warning' => 'manual',
                        'info' => 'import',
                    ]),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('base_currency_id')
                    ->relationship('baseCurrency', 'code')
                    ->label('From Currency'),

                SelectFilter::make('target_currency_id')
                    ->relationship('targetCurrency', 'code')
                    ->label('To Currency'),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Filter::make('date')
                    ->form([
                        DatePicker::make('date_from'),
                        DatePicker::make('date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('duplicate')
                    ->label('Use for Today')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (ExchangeRate $record) {
                        $newRecord = $record->replicate(['date']);
                        $newRecord->date = today();
                        $newRecord->save();
                    })
                    ->requiresConfirmation()
                    ->color('success'),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
