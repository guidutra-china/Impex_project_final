<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use App\Services\Export\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use App\Filament\Traits\HasAdvancedFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OrdersTable
{
    use HasAdvancedFilters;
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
                    ->label(__('fields.currency'))
                    ->sortable(),

               TextColumn::make('commission_percent')
                    ->label('Commission')
                    ->suffix('%')
                    ->sortable(),

               TextColumn::make('total_amount')
                    ->label(__('fields.total'))
                    ->money(fn ($record) => $record->currency?->code ?? 'USD')
                    ->sortable()
                    ->getStateUsing(function (Order $record) {
                        // Calculate total from items if total_amount is 0 or null
                        if (!$record->total_amount || $record->total_amount == 0) {
                            return $record->items->sum(function ($item) {
                                return $item->quantity * $item->requested_unit_price;
                            });
                        }
                        return $record->total_amount / 100;
                    }),

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
                    ])
                    ->multiple()
                    ->label(__('fields.status')),

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label(__('fields.customer')),

                SelectFilter::make('currency_id')
                    ->relationship('currency', 'code')
                    ->searchable()
                    ->preload()
                    ->label(__('fields.currency')),

                self::getDateRangeFilter('created_at', 'Created Date'),
                
                self::getDateRangeFilter('updated_at', 'Updated Date'),
                
                self::getTextSearchFilter('order_number', 'RFQ Number'),
                
                self::getTextSearchFilter('customer_nr_rfq', 'Customer Reference'),
                
                Filter::make('has_quotes')
                    ->label('Has Quotes')
                    ->query(fn (Builder $query): Builder => $query->has('supplierQuotes'))
                    ->toggle(),
                
                Filter::make('no_quotes')
                    ->label('No Quotes Yet')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('supplierQuotes'))
                    ->toggle(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                EditAction::make(),
                
                // PDF Export - Now first and with smart label
                Action::make('export_pdf')
                    ->label(function (Order $record) {
                        $latestDoc = $record->generatedDocuments()
                            ->where('document_type', 'rfq')
                            ->where('format', 'pdf')
                            ->latest('generated_at')
                            ->first();
                        
                        if (!$latestDoc) {
                            return 'Generate PDF';
                        }
                        
                        // Check if RFQ was modified after last generation
                        if ($record->updated_at > $latestDoc->generated_at) {
                            return 'Regenerate PDF';
                        }
                        
                        return 'View PDF';
                    })
                    ->icon(function (Order $record) {
                        $latestDoc = $record->generatedDocuments()
                            ->where('document_type', 'rfq')
                            ->where('format', 'pdf')
                            ->latest('generated_at')
                            ->first();
                        
                        if (!$latestDoc) {
                            return 'heroicon-o-document-arrow-down';
                        }
                        
                        if ($record->updated_at > $latestDoc->generated_at) {
                            return 'heroicon-o-arrow-path';
                        }
                        
                        return 'heroicon-o-eye';
                    })
                    ->color(function (Order $record) {
                        $latestDoc = $record->generatedDocuments()
                            ->where('document_type', 'rfq')
                            ->where('format', 'pdf')
                            ->latest('generated_at')
                            ->first();
                        
                        if (!$latestDoc) {
                            return 'gray';
                        }
                        
                        if ($record->updated_at > $latestDoc->generated_at) {
                            return 'warning';
                        }
                        
                        return 'success';
                    })
                    ->action(function ($record) {
                        try {
                            // Check if document already exists
                            $existingDoc = $record->generatedDocuments()
                                ->where('document_type', 'rfq')
                                ->where('format', 'pdf')
                                ->latest('generated_at')
                                ->first();
                            
                            // Check if RFQ was modified after last generation
                            $needsRegeneration = $existingDoc && ($record->updated_at > $existingDoc->generated_at);
                            
                            if ($existingDoc && Storage::exists($existingDoc->file_path) && !$needsRegeneration) {
                                // Download existing document (no changes)
                                Notification::make()
                                    ->info()
                                    ->title('Downloading existing PDF')
                                    ->body('This document is up to date. Check Documents for history.')
                                    ->send();
                                
                                return response()->download(
                                    storage_path('app/' . $existingDoc->file_path),
                                    $existingDoc->filename
                                );
                            }
                            
                            // Generate new document
                            $pdfService = app(PdfExportService::class);
                            $document = $pdfService->generate(
                                $record,
                                'rfq',
                                'pdf.rfq.template'
                            );
                            
                            // Auto-transition: draft → sent when RFQ PDF is generated for the first time
                            if ($record->status === 'draft' && !$record->sent_at) {
                                $record->update([
                                    'status' => 'sent',
                                    'sent_at' => now(),
                                ]);
                            }
                            
                            $message = $needsRegeneration ? 'RFQ PDF regenerated successfully' : 'RFQ PDF generated successfully';
                            $body = $needsRegeneration ? 'New version created due to RFQ changes' : 'Document saved to Documents History';
                            
                            Notification::make()
                                ->success()
                                ->title($message)
                                ->body($body)
                                ->send();
                            
                            return response()->download(
                                storage_path('app/' . $document->file_path),
                                $document->filename
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('PDF Generation Failed')
                                ->body($e->getMessage())
                                ->send();
                            
                            \Log::error('RFQ PDF generation failed', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            
                            throw $e;
                        }
                    }),
                
                // Excel Export - Now second and with smart label
                Action::make('generate_rfq_excel')
                    ->label(function (Order $record) {
                        $latestDoc = $record->generatedDocuments()
                            ->where('document_type', 'rfq')
                            ->where('format', 'xlsx')
                            ->latest('generated_at')
                            ->first();
                        
                        if (!$latestDoc) {
                            return 'Generate Excel';
                        }
                        
                        // Check if RFQ was modified after last generation
                        if ($record->updated_at > $latestDoc->generated_at) {
                            return 'Regenerate Excel';
                        }
                        
                        return 'View Excel';
                    })
                    ->icon(function (Order $record) {
                        $latestDoc = $record->generatedDocuments()
                            ->where('document_type', 'rfq')
                            ->where('format', 'xlsx')
                            ->latest('generated_at')
                            ->first();
                        
                        if (!$latestDoc) {
                            return 'heroicon-o-document-arrow-down';
                        }
                        
                        if ($record->updated_at > $latestDoc->generated_at) {
                            return 'heroicon-o-arrow-path';
                        }
                        
                        return 'heroicon-o-eye';
                    })
                    ->color(function (Order $record) {
                        $latestDoc = $record->generatedDocuments()
                            ->where('document_type', 'rfq')
                            ->where('format', 'xlsx')
                            ->latest('generated_at')
                            ->first();
                        
                        if (!$latestDoc) {
                            return 'primary';
                        }
                        
                        if ($record->updated_at > $latestDoc->generated_at) {
                            return 'warning';
                        }
                        
                        return 'success';
                    })
                    ->action(function (Order $record) {
                        try {
                            // Check if document already exists
                            $existingDoc = $record->generatedDocuments()
                                ->where('document_type', 'rfq')
                                ->where('format', 'xlsx')
                                ->latest('generated_at')
                                ->first();
                            
                            // Check if RFQ was modified after last generation
                            $needsRegeneration = $existingDoc && ($record->updated_at > $existingDoc->generated_at);
                            
                            if ($existingDoc && Storage::exists($existingDoc->file_path) && !$needsRegeneration) {
                                // Download existing document (no changes)
                                Notification::make()
                                    ->info()
                                    ->title('Downloading existing Excel')
                                    ->body('This document is up to date. Check Documents for history.')
                                    ->send();
                                
                                return response()->download(
                                    storage_path('app/' . $existingDoc->file_path),
                                    $existingDoc->filename
                                );
                            }
                            
                            // Generate new document
                            $rfqService = app(\App\Services\RFQExcelService::class);
                            $filePath = $rfqService->generateRFQ($record);
                            
                            $message = $needsRegeneration ? 'RFQ Excel regenerated successfully' : 'RFQ Excel generated successfully';
                            $body = $needsRegeneration ? 'New version created due to RFQ changes' : 'Document saved to Documents History';
                            
                            Notification::make()
                                ->success()
                                ->title($message)
                                ->body($body)
                                ->send();
                            
                            // Don't delete the file - it's stored permanently in documents/rfq/
                            return response()->download($filePath, basename($filePath));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('RFQ Generation Failed')
                                ->body($e->getMessage())
                                ->send();
                            
                            \Log::error('RFQ Excel generation failed', [
                                'order_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }),
                
                Action::make('view_comparison')
                    ->label('Compare Quotes')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Order $record): string => route('filament.admin.pages.quote-comparison', ['order' => $record->id]))
                    ->visible(fn (Order $record) => $record->supplierQuotes()->count() > 0),
                
                // Request Quotes button REMOVED per user request
                
                // Manual Status Transition Actions
                Action::make('cancel_rfq')
                    ->label('Cancel RFQ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('RFQ cancelled')
                            ->body("RFQ {$record->order_number} has been cancelled.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancel RFQ')
                    ->modalDescription('Are you sure you want to cancel this RFQ? This action can be reversed by reopening.')
                    ->visible(fn (Order $record) => !in_array($record->status, ['cancelled', 'completed'])),
                
                Action::make('reopen_rfq')
                    ->label('Reopen RFQ')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => 'draft',
                            'cancelled_at' => null,
                            'completed_at' => null,
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('RFQ reopened')
                            ->body("RFQ {$record->order_number} has been reopened as draft.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reopen RFQ')
                    ->modalDescription('This will reset the RFQ to draft status. You can regenerate documents and continue the process.')
                    ->visible(fn (Order $record) => in_array($record->status, ['cancelled', 'completed'])),
                
                Action::make('mark_complete')
                    ->label('Mark Complete')
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
                            ->body("RFQ {$record->order_number} has been marked as completed.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Complete')
                    ->modalDescription('Mark this RFQ as completed. This should be done after the purchase order is generated.')
                    ->visible(fn (Order $record) => $record->status === 'approved'),
                
                // Old cancel/reopen buttons removed - replaced with new workflow buttons above
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('export_excel')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $excelService = app(\App\Services\Export\ExcelExportService::class);
                            
                            // Create a temporary collection export
                            $filename = 'rfqs_export_' . now()->format('Y-m-d_His') . '.xlsx';
                            $path = storage_path('app/temp/' . $filename);
                            
                            // Ensure temp directory exists
                            if (!file_exists(storage_path('app/temp'))) {
                                mkdir(storage_path('app/temp'), 0755, true);
                            }
                            
                            // Create Excel file with filtered records
                            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                            $sheet = $spreadsheet->getActiveSheet();
                            
                            // Headers
                            $sheet->setCellValue('A1', 'RFQ Number');
                            $sheet->setCellValue('B1', 'Customer Ref');
                            $sheet->setCellValue('C1', 'Customer');
                            $sheet->setCellValue('D1', 'Status');
                            $sheet->setCellValue('E1', 'Currency');
                            $sheet->setCellValue('F1', 'Created At');
                            
                            // Data
                            $row = 2;
                            foreach ($records as $record) {
                                $sheet->setCellValue('A' . $row, $record->order_number);
                                $sheet->setCellValue('B' . $row, $record->customer_nr_rfq ?? 'N/A');
                                $sheet->setCellValue('C' . $row, $record->customer?->name ?? 'N/A');
                                $sheet->setCellValue('D' . $row, ucfirst($record->status));
                                $sheet->setCellValue('E' . $row, $record->currency?->code ?? 'N/A');
                                $sheet->setCellValue('F' . $row, $record->created_at->format('Y-m-d H:i'));
                                $row++;
                            }
                            
                            // Auto-size columns
                            foreach (range('A', 'F') as $col) {
                                $sheet->getColumnDimension($col)->setAutoSize(true);
                            }
                            
                            // Save file
                            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                            $writer->save($path);
                            
                            // Download
                            return response()->download($path, $filename)->deleteFileAfterSend(true);
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
