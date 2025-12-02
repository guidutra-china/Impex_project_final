<?php

namespace App\Filament\Resources\ProformaInvoice;

use App\Filament\Resources\ProformaInvoice\Schemas\ProformaInvoiceForm;
use App\Models\ProformaInvoice;
use App\Services\Export\ExcelExportService;
use App\Services\Export\PdfExportService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ProformaInvoiceResource extends Resource
{
    protected static ?string $model = ProformaInvoice::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Proforma Invoices';

    protected static ?string $modelLabel = 'Proforma Invoice';

    protected static ?string $pluralModelLabel = 'Proforma Invoices';

    protected static UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ProformaInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
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
            ->actions([
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
                        
                        return Storage::download(
                            $document->file_path,
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
                        
                        return Storage::download(
                            $document->file_path,
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProformaInvoices::route('/'),
            'create' => Pages\CreateProformaInvoice::route('/create'),
            'edit' => Pages\EditProformaInvoice::route('/{record}/edit'),
            'view' => Pages\ViewProformaInvoice::route('/{record}'),
        ];
    }
}
