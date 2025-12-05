<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use BackedEnum;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'proformaInvoices';

    protected static ?string $title = 'Proforma Invoices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'proforma_number';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('proforma_number')
                    ->label('Proforma #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->label('Issue Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('validity_date')
                    ->label('Valid Until')
                    ->date('Y-m-d')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'warning' => 'draft',
                        'info' => 'sent',
                        'primary' => 'confirmed',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'expired',
                    ]),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD', 100)
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                // Pivot columns from shipment_invoices table
                TextColumn::make('pivot.total_items')
                    ->label('Items in Shipment')
                    ->alignCenter()
                    ->default(0),

                TextColumn::make('pivot.total_quantity')
                    ->label('Qty in Shipment')
                    ->alignCenter()
                    ->default(0),

                TextColumn::make('pivot.total_weight')
                    ->label('Weight (kg)')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                    ->alignEnd(),

                TextColumn::make('pivot.total_volume')
                    ->label('Volume (m³)')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 3) : '0.000')
                    ->alignEnd(),

                BadgeColumn::make('pivot.status')
                    ->label('Shipment Status')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state ?? 'pending')))
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial_shipped',
                        'success' => 'fully_shipped',
                    ]),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Attach Proforma Invoice')
                    ->color('info')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function ($query) {
                        $shipment = $this->getOwnerRecord();
                        
                        // Only show proforma invoices that:
                        // 1. Belong to the same customer as the shipment
                        // 2. Are approved or confirmed
                        // 3. Have items
                        return $query->whereHas('items')
                            ->where('customer_id', $shipment->customer_id)
                            ->whereIn('status', ['confirmed', 'approved']);
                    })
                    ->after(function ($record, $livewire) {
                        // Recalculate pivot totals after attaching
                        $shipment = $livewire->getOwnerRecord();
                        $pivotRecord = $shipment->shipmentInvoices()
                            ->where('proforma_invoice_id', $record->id)
                            ->first();
                        
                        if ($pivotRecord) {
                            $pivotRecord->calculateTotals();
                        }
                    }),
            ])
            ->recordActions([
                DetachAction::make()
                    ->requiresConfirmation()
                    ->before(function ($record, $livewire) {
                        // Remove all shipment items from this proforma invoice before detaching
                        $shipment = $livewire->getOwnerRecord();
                        $shipment->items()
                            ->whereHas('proformaInvoiceItem', function ($query) use ($record) {
                                $query->where('proforma_invoice_id', $record->id);
                            })
                            ->delete();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('No proforma invoices attached')
            ->emptyStateDescription('Attach proforma invoices from the client to this shipment to start adding items.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }
}
