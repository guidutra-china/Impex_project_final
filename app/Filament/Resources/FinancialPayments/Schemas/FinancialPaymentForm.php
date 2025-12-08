<?php
namespace App\Filament\Resources\FinancialPayments\Schemas;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
class FinancialPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment Information')->schema([
                Textarea::make('description')->label(__('fields.description'))->required()->maxLength(65535)->columnSpanFull(),
                Select::make('type')->label(__('fields.type'))->required()->options(['debit' => 'Debit (Payment)', 'credit' => 'Credit (Receipt)'])->default('debit'),
                Select::make('bank_account_id')->label(__('fields.bank_name'))->relationship('bankAccount', 'account_name')->searchable()->preload()->required(),
                Select::make('payment_method_id')->label('Payment Method')->relationship('paymentMethod', 'name')->searchable()->preload()->required(),
                DatePicker::make('payment_date')->label('Payment Date')->required()->default(now()),
            ])->columns(2),
            Section::make('Values')->schema([
                TextInput::make('amount')->label(__('fields.amount'))->required()->numeric()->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? '$'),
                Select::make('currency_id')->label(__('fields.currency'))->relationship('currency', 'code')->searchable()->preload()->required()->live()->afterStateUpdated(function ($state, callable $set) {
                    if (!$state) return;
                    $baseCurrency = Currency::where('is_base', true)->first();
                    if (!$baseCurrency) return;
                    $rate = ExchangeRate::getConversionRate($state, $baseCurrency->id, now()->toDateString());
                    $set('exchange_rate_to_base', $rate ?? 1.0);
                }),
                TextInput::make('fee')->label('Fee')->numeric()->default(0)->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? '$'),
                TextInput::make('exchange_rate_to_base')->label(__('fields.exchange_rate'))->numeric()->disabled()->dehydrated(),
            ])->columns(2),
        ]);
    }
}
