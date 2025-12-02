<?php

namespace App\Filament\Resources\ProformaInvoice\Schemas;

use App\Services\Export\ExcelExportService;
use App\Services\Export\PdfExportService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ProformaInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Proforma Invoice Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('proforma_number')
                                    ->label('Proforma Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated')
                                    ->columnSpan(1),

                                TextInput::make('revision_number')
                                    ->label('Revision')
                                    ->numeric()
                                    ->default(1)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Auto-increments when important fields are changed')
                                    ->columnSpan(1),

                                Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label('Customer')
                                    ->columnSpan(1),

                                Select::make('currency_id')
                                    ->relationship('currency', 'code', fn ($query) => $query->where('is_active', true))
                                    ->required()
                                    ->searchable()
                                    ->default(1)
                                    ->preload()
                                    ->label('Currency')
                                    ->columnSpan(1),

                                DatePicker::make('issue_date')
                                    ->label('Issue Date')
                                    ->required()
                                    ->default(now())
                                    ->columnSpan(1),

                                DatePicker::make('valid_until')
                                    ->label('Valid Until')
                                    ->required()
                                    ->default(now()->addDays(30))
                                    ->afterOrEqual('issue_date')
                                    ->columnSpan(1),

                                Select::make('payment_term_id')
                                    ->relationship('paymentTerm', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('Payment Terms')
                                    ->columnSpan(1),

                                Select::make('incoterm')
                                    ->options([
                                        'EXW' => 'EXW - Ex Works',
                                        'FCA' => 'FCA - Free Carrier',
                                        'CPT' => 'CPT - Carriage Paid To',
                                        'CIP' => 'CIP - Carriage and Insurance Paid To',
                                        'DAP' => 'DAP - Delivered at Place',
                                        'DPU' => 'DPU - Delivered at Place Unloaded',
                                        'DDP' => 'DDP - Delivered Duty Paid',
                                        'FAS' => 'FAS - Free Alongside Ship',
                                        'FOB' => 'FOB - Free on Board',
                                        'CFR' => 'CFR - Cost and Freight',
                                        'CIF' => 'CIF - Cost, Insurance and Freight',
                                    ])
                                    ->searchable()
                                    ->label('INCOTERMS')
                                    ->helperText('International Commercial Terms')
                                    ->columnSpan(1),

                                TextInput::make('incoterm_location')
                                    ->label('INCOTERMS Location')
                                    ->placeholder('e.g., Shanghai Port, New York')
                                    ->helperText('Specific location for the INCOTERM')
                                    ->columnSpan(1),

                                DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->afterOrEqual('issue_date')
                                    ->columnSpan(1),

                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                        'expired' => 'Expired',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('draft')
                                    ->columnSpan(1),

                                TextInput::make('exchange_rate')
                                    ->label('Exchange Rate')
                                    ->numeric()
                                    ->default(1.0)
                                    ->minValue(0.000001)
                                    ->step(0.000001)
                                    ->helperText('Rate to convert to base currency')
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),

                Section::make('Deposit Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Toggle::make('deposit_required')
                                    ->label('Deposit Required')
                                    ->default(false)
                                    ->live()
                                    ->columnSpan(2),

                                TextInput::make('deposit_percent')
                                    ->label('Deposit %')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->visible(fn (Get $get) => $get('deposit_required'))
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $total = (float) $get('total');
                                        if ($state && $total > 0) {
                                            $depositAmount = $total * ($state / 100);
                                            $set('deposit_amount', $depositAmount);
                                        }
                                    })
                                    ->columnSpan(1),

                                TextInput::make('deposit_amount')
                                    ->label('Deposit Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->visible(fn (Get $get) => $get('deposit_required'))
                                    ->columnSpan(1),

                                Toggle::make('deposit_received')
                                    ->label('Deposit Received')
                                    ->default(false)
                                    ->visible(fn (Get $get) => $get('deposit_required'))
                                    ->columnSpan(1),

                                TextInput::make('deposit_payment_method')
                                    ->label('Payment Method')
                                    ->visible(fn (Get $get) => $get('deposit_received'))
                                    ->columnSpan(1),

                                TextInput::make('deposit_payment_reference')
                                    ->label('Payment Reference')
                                    ->visible(fn (Get $get) => $get('deposit_received'))
                                    ->columnSpan(2),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

                Section::make('Amounts')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Placeholder::make('subtotal_display')
                                    ->label('Subtotal')
                                    ->content(fn ($record) => $record 
                                        ? ($record->currency?->code ?? 'USD') . ' ' . number_format($record->subtotal, 2)
                                        : '$0.00'
                                    )
                                    ->columnSpan(1),

                                TextInput::make('tax')
                                    ->label('Tax')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $subtotal = (float) $get('subtotal') ?? 0;
                                        $tax = (float) $get('tax') ?? 0;
                                        $set('total', $subtotal + $tax);
                                    })
                                    ->columnSpan(1),

                                Placeholder::make('total_display')
                                    ->label('Total')
                                    ->content(fn ($record) => $record 
                                        ? ($record->currency?->code ?? 'USD') . ' ' . number_format($record->total, 2)
                                        : '$0.00'
                                    )
                                    ->columnSpan(1),
                            ])
                            ->columns(3),
                    ])
                    ->columnSpanFull(),

                Section::make('Notes & Terms')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Textarea::make('customer_notes')
                                    ->label('Customer Notes')
                                    ->helperText('Notes visible to customer')
                                    ->rows(3)
                                    ->columnSpan(1),

                                Textarea::make('notes')
                                    ->label('Internal Notes')
                                    ->helperText('Internal notes (not visible to customer)')
                                    ->rows(3)
                                    ->columnSpan(1),

                                Textarea::make('terms_and_conditions')
                                    ->label('Terms and Conditions')
                                    ->helperText('Terms and conditions for this proforma')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

                Section::make('Export Documents')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Placeholder::make('export_actions')
                                    ->label('')
                                    ->content(fn ($record) => $record ? 'Click the buttons below to generate and download documents' : 'Save the proforma first to enable export')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->headerActions([
                        Action::make('export_pdf')
                            ->label('Export PDF')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('gray')
                            ->visible(fn ($record) => $record !== null)
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
                            ->label('Export Excel')
                            ->icon('heroicon-o-table-cells')
                            ->color('success')
                            ->visible(fn ($record) => $record !== null)
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
                    ->visible(fn ($record) => $record !== null)
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
