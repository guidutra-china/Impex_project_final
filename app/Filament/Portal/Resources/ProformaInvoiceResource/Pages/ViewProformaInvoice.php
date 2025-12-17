<?php

namespace App\Filament\Portal\Resources\ProformaInvoiceResource\Pages;

use App\Filament\Portal\Resources\ProformaInvoiceResource;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProformaInvoice extends ViewRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Proforma Invoice Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('invoice_number')
                                    ->label('Invoice Number'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'sent' => 'info',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('invoice_date')
                                    ->label('Invoice Date')
                                    ->date(),
                            ]),
                    ]),

                Section::make('Order Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('order.order_number')
                                    ->label('Order Number'),
                                TextEntry::make('order.status')
                                    ->label('Order Status')
                                    ->badge(),
                            ]),
                    ]),

                Section::make('Financial Information')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('currency')
                                    ->label('Currency'),
                                TextEntry::make('exchange_rate')
                                    ->label('Exchange Rate')
                                    ->numeric(4),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money(fn ($record) => $record->currency ?? 'USD'),
                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->money(fn ($record) => $record->currency ?? 'USD')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('Payment Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('paymentTerm.name')
                                    ->label('Payment Terms'),
                                TextEntry::make('incoterm')
                                    ->label('Incoterm'),
                            ]),
                    ]),

                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Product'),
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->limit(50),
                                        TextEntry::make('quantity')
                                            ->label('Quantity'),
                                        TextEntry::make('unit_price')
                                            ->label('Unit Price')
                                            ->money(fn ($record) => $record->proformaInvoice->currency ?? 'USD'),
                                        TextEntry::make('total_price')
                                            ->label('Total')
                                            ->money(fn ($record) => $record->proformaInvoice->currency ?? 'USD')
                                            ->weight('bold'),
                                    ]),
                            ])
                            ->columns(1)
                            ->contained(true),
                    ]),

                Section::make('Approval Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('approved_at')
                                    ->label('Approved At')
                                    ->dateTime()
                                    ->placeholder('Not approved yet'),
                                TextEntry::make('approvedBy.name')
                                    ->label('Approved By')
                                    ->placeholder('Not approved yet'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->status === 'approved'),
            ]);
    }
}
