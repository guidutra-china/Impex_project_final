<?php

namespace App\Filament\Portal\Resources\PurchaseOrderResource\Pages;

use App\Filament\Portal\Resources\PurchaseOrderResource;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Purchase Order Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('po_number')
                                    ->label('PO Number'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'sent' => 'info',
                                        'confirmed' => 'warning',
                                        'in_production' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make('Supplier Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('supplier.name')
                                    ->label('Supplier Name'),
                                TextEntry::make('supplierQuote.quote_number')
                                    ->label('Quote Number'),
                            ]),
                    ]),

                Section::make('Financial Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('currency')
                                    ->label('Currency'),
                                TextEntry::make('exchange_rate')
                                    ->label('Exchange Rate')
                                    ->numeric(4),
                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->money(fn ($record) => $record->currency ?? 'USD')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success'),
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
                                        TextEntry::make('quantity')
                                            ->label('Quantity'),
                                        TextEntry::make('unit_price')
                                            ->label('Unit Price')
                                            ->money(fn ($record) => $record->purchaseOrder->currency ?? 'USD'),
                                        TextEntry::make('total_price')
                                            ->label('Total')
                                            ->money(fn ($record) => $record->purchaseOrder->currency ?? 'USD')
                                            ->weight('bold'),
                                        TextEntry::make('status')
                                            ->badge(),
                                    ]),
                            ])
                            ->columns(1)
                            ->contained(true),
                    ]),
            ]);
    }
}
