<?php

namespace App\Filament\Resources\SalesInvoices\Tables;

use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('revision_number')
                    ->label('Rev')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quote.quote_number')
                    ->label('Quote #')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'success' => 'paid',
                        'danger' => ['overdue', 'cancelled'],
                        'gray' => 'superseded',
                    ])
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),

                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency->code ?? 'USD', divideBy: 100)
                    ->sortable(),

                TextColumn::make('commission')
                    ->label('Commission')
                    ->money(fn ($record) => $record->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('currency.code')
                    ->label('Currency')
                    ->toggleable(),

                TextColumn::make('payment_date')
                    ->label('Paid On')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                        'superseded' => 'Superseded',
                    ])
                    ->multiple(),

                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('currency')
                    ->relationship('currency', 'code'),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->where('status', '!=', 'paid')
                        ->where('status', '!=', 'cancelled')
                        ->where('status', '!=', 'superseded')
                        ->where('due_date', '<', now()))
                    ->label('Overdue Only'),

                Tables\Filters\Filter::make('unpaid')
                    ->query(fn ($query) => $query->where('status', '!=', 'paid')
                        ->where('status', '!=', 'cancelled')
                        ->where('status', '!=', 'superseded'))
                    ->label('Unpaid Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['sent', 'overdue']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'payment_date' => now(),
                        ]);
                    })
                    ->successNotificationTitle('Invoice marked as paid'),

                Tables\Actions\Action::make('mark_as_sent')
                    ->label('Mark as Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                    })
                    ->successNotificationTitle('Invoice marked as sent'),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => !in_array($record->status, ['paid', 'cancelled', 'superseded']))
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'cancellation_reason' => $data['cancellation_reason'],
                        ]);
                    })
                    ->successNotificationTitle('Invoice cancelled'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
