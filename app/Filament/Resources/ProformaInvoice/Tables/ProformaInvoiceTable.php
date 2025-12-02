<?php

namespace App\Filament\Resources\ProformaInvoice\Tables;

use App\Services\Export\ExcelExportService;
use App\Services\Export\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ProformaInvoiceTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('proforma_number')
                    ->label('Proforma #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('revision_number')
                    ->label('Rev.')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->label('Issue Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),

                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'warning',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('deposit_required')
                    ->label('Deposit')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray')
                    ->formatStateUsing(fn (bool $state, $record): string => 
                        $state 
                            ? ($record->deposit_received ? 'Received' : 'Required') 
                            : 'No'
                    )
                    ->toggleable(),

                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                
                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function ($record) {
                        $pdfService = app(PdfExportService::class);
                        $document = $pdfService->generate(
                            $record,
                            'proforma_invoice',
                            'pdf.proforma-invoice.template',
                            [],
                            ['revision_number' => $record->revision_number]
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('PDF generated successfully')
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
                        $excelService = app(ExcelExportService::class);
                        $document = $excelService->generate(
                            $record,
                            'proforma_invoice',
                            ['revision_number' => $record->revision_number]
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('Excel generated successfully')
                            ->send();
                        
                        return response()->download(
                            storage_path('app/' . $document->file_path),
                            $document->filename
                        );
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
