<?php

namespace App\Filament\Resources\SupplierQuotes\Tables;

use App\Services\Export\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SupplierQuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.order_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ]),

                TextColumn::make('currency.code')
                    ->label('Currency'),

                TextColumn::make('total_price_after_commission')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                    ->sortable(),

                TextColumn::make('locked_exchange_rate')
                    ->label('Rate')
                    ->numeric(decimalPlaces: 4)
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_latest')
                    ->label('Latest')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),

                SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->label('Order'),

                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier'),

                TernaryFilter::make('is_latest')
                    ->label('Latest Version Only'),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function ($record) {
                        $pdfService = app(PdfExportService::class);
                        $document = $pdfService->generate(
                            $record,
                            'supplier_quote',
                            'pdf.supplier-quote.template',
                            [],
                            ['revision_number' => $record->revision_number]
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('Supplier Quote PDF generated successfully')
                            ->send();
                        
                        return response()->download(
                            storage_path('app/' . $document->file_path),
                            $document->filename
                        );
                    }),
                
                Action::make('export_excel')
                    ->label('Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function ($record) {
                        $excelService = app(\App\Services\Export\ExcelExportService::class);
                        $document = $excelService->generate(
                            $record,
                            'supplier_quote',
                            ['revision_number' => $record->revision_number]
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('Supplier Quote Excel generated successfully')
                            ->send();
                        
                        return response()->download(
                            storage_path('app/' . $document->file_path),
                            $document->filename
                        );
                    }),
                
                Action::make('calculate_commission')
                    ->label('Recalculate')
                    ->icon('heroicon-o-calculator')
                    ->action(function ($record) {
                        $record->calculateCommission();
                        $record->lockExchangeRate();
                    })
                    ->requiresConfirmation()
                    ->color('warning'),
                
                Action::make('create_purchase_order')
                    ->label('Create PO')
                    ->icon('heroicon-o-document-plus')
                    ->action(function ($record) {
                        // Create PO from Supplier Quote
                        $po = \App\Models\PurchaseOrder::create([
                            // po_number will be auto-generated
                            'revision_number' => 1,
                            'po_date' => now(),
                            'status' => 'draft',
                            'order_id' => $record->order_id,
                            'supplier_quote_id' => $record->id,
                            'supplier_id' => $record->supplier_id,
                            'currency_id' => $record->currency_id,
                            'exchange_rate' => $record->locked_exchange_rate ?? $record->currency?->exchange_rate ?? 1,
                            'base_currency_id' => \App\Models\Currency::where('code', 'USD')->first()?->id,
                            'subtotal' => 0,
                            'total' => 0,
                            'total_base_currency' => 0,
                        ]);
                        
                        // Create PO items from quote items
                        foreach ($record->items as $quoteItem) {
                            \App\Models\PurchaseOrderItem::create([
                                'purchase_order_id' => $po->id,
                                'product_id' => $quoteItem->product_id,
                                'product_name' => $quoteItem->product?->name ?? '',
                                'product_sku' => $quoteItem->product?->sku ?? '',
                                'quantity' => $quoteItem->quantity,
                                'unit_cost' => $quoteItem->unit_price_before_commission / 100,
                                'total_cost' => $quoteItem->total_price_before_commission / 100,
                                'notes' => $quoteItem->supplier_notes ?? '',
                            ]);
                        }
                        
                        // Recalculate totals
                        $po->recalculateTotals();
                        
                        // Redirect to edit PO
                        return redirect()->route('filament.admin.resources.purchase-orders.edit', ['record' => $po->id]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Create Purchase Order')
                    ->modalDescription('This will create a new Purchase Order from this Supplier Quote.')
                    ->modalSubmitActionLabel('Create PO')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'accepted'),
                
                // Status Transition Actions
                Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'accepted',
                            'accepted_at' => now(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Quote accepted')
                            ->body("Supplier Quote {$record->quote_number} has been accepted.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Accept Supplier Quote')
                    ->modalDescription('Are you sure you want to accept this quote?')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'sent'])),
                
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejected_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Quote rejected')
                            ->body("Supplier Quote {$record->quote_number} has been rejected.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'sent'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
