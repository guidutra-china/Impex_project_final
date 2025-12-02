<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use App\Services\Export\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('order_number')
                    ->label('RFQ Number')
                    ->searchable()
                    ->sortable(),

               TextColumn::make('customer_nr_rfq')
                    ->label('Customer Ref.')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

               TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),

               TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->toggleable()
                    ->placeholder('No tags'),

               BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'info' => 'quoted',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),

               TextColumn::make('currency.code')
                    ->label('Currency')
                    ->sortable(),

               TextColumn::make('commission_percent')
                    ->label('Commission')
                    ->suffix('%')
                    ->sortable(),

               TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                    ->sortable(),

               TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'quoted' => 'Quoted',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer'),

                SelectFilter::make('currency_id')
                    ->relationship('currency', 'code')
                    ->label('Currency'),
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
                            'rfq',
                            'pdf.rfq.template'
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('RFQ PDF generated successfully')
                            ->send();
                        
                        return Storage::download(
                            $document->file_path,
                            $document->filename
                        );
                    }),
                
                Action::make('view_comparison')
                    ->label('Compare Quotes')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Order $record): string => route('filament.admin.pages.quote-comparison', ['order' => $record->id]))
                    ->visible(fn (Order $record) => $record->supplierQuotes()->count() > 0),
                
                Action::make('request_quotes_bulk')
                    ->label('Request Quotes')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->form([
                        Select::make('suppliers')
                            ->label('Select Suppliers')
                            ->multiple()
                            ->options(Supplier::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select multiple suppliers to request quotes from'),
                        
                        Textarea::make('message')
                            ->label('Message')
                            ->default('Please provide your best quote for the attached RFQ.')
                            ->rows(3)
                            ->helperText('This message will be included in the email to suppliers'),
                    ])
                    ->action(function (Order $record, array $data) {
                        $count = 0;
                        $emailsSent = 0;
                        
                        foreach ($data['suppliers'] as $supplierId) {
                            $supplier = Supplier::find($supplierId);
                            
                            // Create supplier quote if not exists
                            $existingQuote = SupplierQuote::where('order_id', $record->id)
                                ->where('supplier_id', $supplierId)
                                ->first();
                            
                            if (!$existingQuote) {
                                SupplierQuote::create([
                                    'order_id' => $record->id,
                                    'supplier_id' => $supplierId,
                                    'status' => 'sent',
                                    'currency_id' => $record->currency_id,
                                ]);
                                
                                $count++;
                            }
                            
                            // Send email to supplier if email exists
                            if ($supplier && $supplier->email) {
                                try {
                                    Mail::to($supplier->email)
                                        ->send(new \App\Mail\QuoteRequestMail($record, $supplier, $data['message']));
                                    $emailsSent++;
                                } catch (\Exception $e) {
                                    \Log::error('Failed to send quote request email to ' . $supplier->email . ': ' . $e->getMessage());
                                }
                            }
                        }
                        
                        // Update RFQ status to processing
                        if ($count > 0 && $record->status === 'pending') {
                            $record->update(['status' => 'processing']);
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Quote requests sent')
                            ->body("Created {$count} quote requests. Sent {$emailsSent} emails to suppliers.")
                            ->send();
                    })
                    ->visible(fn (Order $record) => in_array($record->status, ['pending', 'processing'])),
                
                // Status Transition Actions
                Action::make('start_processing')
                    ->label('Start Processing')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => 'processing',
                            'processing_started_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('RFQ processing started')
                            ->body("RFQ {$record->order_number} is now being processed.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->status === 'pending'),
                
                Action::make('mark_as_quoted')
                    ->label('Mark as Quoted')
                    ->icon('heroicon-o-document-check')
                    ->color('info')
                    ->action(function (Order $record) {
                        $quoteCount = $record->supplierQuotes()->count();
                        
                        if ($quoteCount === 0) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot mark as quoted')
                                ->body('No supplier quotes found for this RFQ.')
                                ->send();
                            return;
                        }
                        
                        $record->update([
                            'status' => 'quoted',
                            'quoted_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('RFQ marked as quoted')
                            ->body("RFQ {$record->order_number} has {$quoteCount} supplier quote(s).")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->status === 'processing'),
                
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('RFQ completed')
                            ->body("RFQ {$record->order_number} has been completed successfully.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Complete RFQ')
                    ->modalDescription('Are you sure you want to mark this RFQ as completed?')
                    ->visible(fn (Order $record) => $record->status === 'quoted'),
                
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Order $record, array $data) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'cancellation_reason' => $data['cancellation_reason'],
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('RFQ cancelled')
                            ->body("RFQ {$record->order_number} has been cancelled.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => in_array($record->status, ['pending', 'processing', 'quoted'])),
                
                Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => 'pending',
                            'cancelled_at' => null,
                            'cancellation_reason' => null,
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('RFQ reopened')
                            ->body("RFQ {$record->order_number} has been reopened.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->status === 'cancelled'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}