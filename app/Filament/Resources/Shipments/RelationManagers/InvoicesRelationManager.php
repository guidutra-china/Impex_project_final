<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use App\Repositories\ShipmentRepository;
use App\Repositories\SalesInvoiceRepository;
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
    protected static string $relationship = 'salesInvoices';

    protected static ?string $title = 'Sales Invoices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected ?ShipmentRepository $shipmentRepository = null;
    protected ?SalesInvoiceRepository $salesInvoiceRepository = null;

    public function mount(): void
    {
        parent::mount();
        $this->shipmentRepository = app(ShipmentRepository::class);
        $this->salesInvoiceRepository = app(SalesInvoiceRepository::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ($this->shipmentRepository ?? app(ShipmentRepository::class))->getInvoicesQuery($this->getOwnerRecord()->id)
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('Y-m-d')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->colors([
                        'warning' => 'draft',
                        'info' => 'sent',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),

                TextColumn::make('total')
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
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Attach Invoice')
                    ->color('info')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function ($query) {
                        // Only show invoices that are not cancelled and have items
                        return $query->whereHas('items')
                            ->where('status', '!=', 'cancelled');
                    })
                    ->after(function ($record, $livewire) {
                        // Recalculate pivot totals after attaching
                        $shipment = $livewire->getOwnerRecord();
                        $pivotRecord = $shipment->shipmentInvoices()
                            ->where('sales_invoice_id', $record->id)
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
                        // Remove all shipment items from this invoice before detaching
                        $shipment = $livewire->getOwnerRecord();
                        $shipment->items()
                            ->whereHas('salesInvoiceItem', function ($query) use ($record) {
                                $query->where('sales_invoice_id', $record->id);
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
            ->emptyStateHeading('No invoices attached')
            ->emptyStateDescription('Attach sales invoices to this shipment to start adding items.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }
}
