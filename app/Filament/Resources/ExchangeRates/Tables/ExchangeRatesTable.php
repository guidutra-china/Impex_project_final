<?php

namespace App\Filament\Resources\SupplierQuotes\ExchangeRates\Tables;

use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExchangeRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('baseCurrency.code')
                    ->label('From')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('targetCurrency.code')
                    ->label('To')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rate')
                    ->numeric(decimalPlaces: 6)
                    ->sortable(),

                Tables\Columns\TextColumn::make('inverse_rate')
                    ->label('Inverse')
                    ->numeric(decimalPlaces: 6)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'success' => 'api',
                        'warning' => 'manual',
                        'info' => 'import',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('base_currency_id')
                    ->relationship('baseCurrency', 'code')
                    ->label('From Currency'),

                Tables\Filters\SelectFilter::make('target_currency_id')
                    ->relationship('targetCurrency', 'code')
                    ->label('To Currency'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_until'),
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
                Tables\Actions\Action::make('duplicate')
                    ->label('Use for Today')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (ExchangeRate $record) {
                        $newRecord = $record->replicate(['date']);
                        $newRecord->date = today();
                        $newRecord->save();
                    })
                    ->requiresConfirmation()
                    ->color('success'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
