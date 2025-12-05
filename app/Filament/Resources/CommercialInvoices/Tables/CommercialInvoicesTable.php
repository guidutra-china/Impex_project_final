<?php

namespace App\Filament\Resources\CommercialInvoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommercialInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('shipment.shipment_number')
                    ->label('Shipment #')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.shipments.shipments.edit', $record->shipment_id)),

                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('importer_name')
                    ->label('Importer')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('exporter_name')
                    ->label('Exporter')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'submitted' => 'Submitted',
                        'cleared' => 'Cleared',
                        'cancelled' => 'Cancelled',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'draft',
                        'primary' => 'issued',
                        'warning' => 'submitted',
                        'success' => 'cleared',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),

                TextColumn::make('currency.code')
                    ->label('Currency')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money(fn ($record) => $record->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('customs_discount_percentage')
                    ->label('Customs Discount')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('incoterm')
                    ->label('Incoterm')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('port_of_loading')
                    ->label('POL')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('port_of_discharge')
                    ->label('POD')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('issued_at')
                    ->label('Issued At')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('issuedBy.name')
                    ->label('Issued By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'submitted' => 'Submitted',
                        'cleared' => 'Cleared',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->relationship('currency', 'code'),

                SelectFilter::make('reason_for_export')
                    ->label('Export Reason')
                    ->options([
                        'sale' => 'Sale',
                        'sample' => 'Sample',
                        'return' => 'Return',
                        'repair' => 'Repair',
                        'gift' => 'Gift',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
