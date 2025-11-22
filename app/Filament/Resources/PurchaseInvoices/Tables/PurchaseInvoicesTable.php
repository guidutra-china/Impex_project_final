<?php

namespace App\Filament\Resources\PurchaseInvoices\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseInvoicesTable
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

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('paymentTerm.name')
                    ->label('Payment Terms')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('purchaseOrder.po_number')
                    ->label('PO #')
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

                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
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
                EditAction::make(),

                Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['sent', 'overdue']))
                    ->form([
                        DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->default(now())
                            ->required(),
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'bank_transfer' => 'Bank Transfer',
                                'credit_card' => 'Credit Card',
                                'cash' => 'Cash',
                                'check' => 'Check',
                                'paypal' => 'PayPal',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->helperText('Transaction ID, check number, etc.'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'payment_reference' => $data['payment_reference'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Invoice marked as paid')
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice Paid')
                            ->body("Invoice {$record->invoice_number} has been marked as paid.")
                    ),

                Action::make('mark_as_sent')
                    ->label('Mark as Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice to Supplier')
                    ->modalDescription(fn ($record) => "Are you sure you want to mark invoice {$record->invoice_number} as sent? This will change the status from Draft to Sent.")
                    ->modalSubmitActionLabel('Yes, Mark as Sent')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                    })
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice Sent')
                            ->body("Invoice {$record->invoice_number} has been marked as sent to supplier.")
                    ),

                Action::make('cancel')
                    ->label('Cancel Invoice')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => !in_array($record->status, ['paid', 'cancelled', 'superseded']))
                    ->modalHeading('Cancel Invoice')
                    ->modalDescription(fn ($record) => "Are you sure you want to cancel invoice {$record->invoice_number}? This action cannot be undone.")
                    ->form([
                        Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(3)
                            ->helperText('Please provide a reason for cancelling this invoice.'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'cancellation_reason' => $data['cancellation_reason'],
                        ]);
                    })
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Invoice Cancelled')
                            ->body("Invoice {$record->invoice_number} has been cancelled.")
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
