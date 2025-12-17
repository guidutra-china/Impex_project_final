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
                    ->label(__('fields.currency')),

                TextColumn::make('total_price_after_commission')
                    ->label(__('fields.total'))
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
                    ->label(__('fields.order')),

                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label(__('fields.supplier')),

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
