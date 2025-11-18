<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Exceptions\MissingExchangeRateException;
use App\Models\ExchangeRate;
use Filament\Actions\Action;
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
                EditAction::make(),
                DeleteAction::make(),
                Action::make('calculate_commission')
                    ->label('Recalculate')
                    ->icon('heroicon-o-calculator')
                    ->action(function ($record) {
                        $record->calculateCommission();
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
