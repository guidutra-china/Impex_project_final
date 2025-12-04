<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Exceptions\MissingExchangeRateException;
use App\Models\ExchangeRate;
use App\Repositories\OrderRepository;
use App\Repositories\SupplierQuoteRepository;
use App\Services\SupplierQuoteImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Exceptions\Halt;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierQuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierQuotes';

    protected static ?string $title = 'Supplier Quotes';

    protected OrderRepository $orderRepository;
    protected SupplierQuoteRepository $quoteRepository;

    public function mount(): void {
        parent::mount();
        $this->orderRepository = app(OrderRepository::class);
        $this->quoteRepository = app(SupplierQuoteRepository::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(1),

                Select::make('currency_id')
                    ->relationship('currency', 'code', fn ($query) => $query->where('is_active', true))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Quote Currency')
                    ->columnSpan(1),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ])
                    ->required()
                    ->default('draft')
                    ->columnSpan(1),

                TextInput::make('validity_days')
                    ->label('Valid for (days)')
                    ->numeric()
                    ->default(30)
                    ->minValue(1)
                    ->columnSpan(1),

                Textarea::make('supplier_notes')
                    ->label('Supplier Notes')
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Internal Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('quote_number')
            ->query(
                $this->orderRepository->getSupplierQuotesQuery($this->getOwnerRecord()->id)
            )
            ->columns([
                TextColumn::make('quote_number')
                    ->searchable(),

                TextColumn::make('supplier.name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('currency.code')
                    ->label('Currency'),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ]),

                TextColumn::make('total_price_after_commission')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100),

                TextColumn::make('locked_exchange_rate')
                    ->label('Rate')
                    ->numeric(decimalPlaces: 4)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valid_until')
                    ->date()
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
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                        try {
                            // Add order_id from the owner record (the Order)
                            $data['order_id'] = $this->getOwnerRecord()->id;
                            
                            $record = $model::create($data);
                            return $record;
                        } catch (MissingExchangeRateException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Missing Exchange Rate')
                                ->body($exception->getMessage())
                                ->persistent()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('register_rate')
                                        ->button()
                                        ->label('Register Exchange Rate')
                                        ->url(route('filament.admin.resources.exchange-rates.create', [
                                            'from_currency_id' => $exception->fromCurrencyId,
                                            'to_currency_id' => $exception->toCurrencyId,
                                            'date' => $exception->date,
                                        ]))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();
                            
                            throw new Halt();
                        }
                    }),
            ])
            ->actions([
                Action::make('import_from_excel')
                    ->label('Import')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('file')
                            ->label('Excel File')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Upload the Excel file returned by the supplier with filled prices.'),
                    ])
                    ->action(function (array $data, $record) {
                        $importService = app(SupplierQuoteImportService::class);
                        
                        // Get the actual file path from Livewire temporary upload
                        $filePath = storage_path('app/' . $data['file']);
                        
                        try {
                            $result = $importService->importFromExcel($record, $filePath);
                            
                            if ($result['success']) {
                                Notification::make()
                                    ->success()
                                    ->title('Import Successful')
                                    ->body($result['message'])
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Import Completed with Warnings')
                                    ->body($result['message'])
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Import Failed')
                                ->body($e->getMessage())
                                ->send();
                        } finally {
                            // Clean up uploaded file
                            if (file_exists($filePath)) {
                                try {
                                    @unlink($filePath);
                                } catch (\Throwable $e) {
                                    // Ignore cleanup errors
                                }
                            }
                        }
                    })
                    ->color('info')
                    ->modalHeading('Import Supplier Quote from Excel')
                    ->modalSubmitActionLabel('Import'),
                    
                EditAction::make(),
                DeleteAction::make(),
                Action::make('calculate_commission')
                    ->label('Recalculate')
                    ->icon('heroicon-o-calculator')
                    ->action(function ($record) {
                        try {
                            $this->quoteRepository->recalculate($record->id);
                            
                            Notification::make()
                                ->success()
                                ->title('Quote Recalculated')
                                ->body('Commission and totals have been recalculated.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Recalculation Failed')
                                ->body($e->getMessage())
                                ->send();

                            \Log::error('Erro ao recalcular cotação', [
                                'id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->color('warning'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Action to register missing exchange rate
     */
    protected function registerExchangeRateAction(): Action
    {
        return Action::make('registerExchangeRate')
            ->form([
                Select::make('from_currency_id')
                    ->label('From Currency')
                    ->relationship('currency', 'code')
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                Select::make('to_currency_id')
                    ->label('To Currency')
                    ->relationship('currency', 'code')
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('rate')
                    ->label('Exchange Rate')
                    ->required()
                    ->numeric()
                    ->step(0.0001)
                    ->helperText('Enter the conversion rate from the first currency to the second currency'),
            ])
            ->action(function (array $data) {
                ExchangeRate::create([
                    'from_currency_id' => $data['from_currency_id'],
                    'to_currency_id' => $data['to_currency_id'],
                    'date' => $data['date'],
                    'rate' => $data['rate'],
                ]);

                Notification::make()
                    ->success()
                    ->title('Exchange Rate Registered')
                    ->body('The exchange rate has been successfully registered. You can now create the quote.')
                    ->send();
            })
            ->modalHeading('Register Exchange Rate')
            ->modalDescription('Please enter the exchange rate for the missing conversion.')
            ->modalSubmitActionLabel('Register');
    }
}
