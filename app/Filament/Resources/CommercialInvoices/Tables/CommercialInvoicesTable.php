<?php

namespace App\Filament\Resources\CommercialInvoices\Tables;

use App\Models\CommercialInvoice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Services\CommercialInvoicePdfService;
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
                    ->sortable(),

                TextColumn::make('shipment.shipment_number')
                    ->label('Shipment #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('shipment_date')
                    ->label('Shipment Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('customs_discount_percentage')
                    ->label('Customs Discount')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'issued' => 'info',
                        'sent' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('incoterm')
                    ->label('Incoterm')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('port_of_loading')
                    ->label('POL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('port_of_discharge')
                    ->label('POD')
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
                        'issued' => 'Issued',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('client_id')
                    ->label('Customer')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('shipment_id')
                    ->label('Shipment')
                    ->relationship('shipment', 'shipment_number')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                
                Action::make('export_pdf_original')
                    ->label('PDF Original')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function ($record) {
                        $pdfService = app(CommercialInvoicePdfService::class);
                        $document = $pdfService->generateOriginalPdf($record);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('PDF Original generated successfully')
                            ->send();
                        
                        return response()->download(
                            storage_path('app/' . $document->file_path),
                            $document->filename
                        );
                    }),
                
                Action::make('export_pdf_customs')
                    ->label('PDF Customs')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(fn ($record) => $record->hasCustomsDiscount())
                    ->action(function ($record) {
                        $pdfService = app(CommercialInvoicePdfService::class);
                        $document = $pdfService->generateCustomsPdf($record);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('PDF Customs generated successfully')
                            ->send();
                        
                        return response()->download(
                            storage_path('app/' . $document->file_path),
                            $document->filename
                        );
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
