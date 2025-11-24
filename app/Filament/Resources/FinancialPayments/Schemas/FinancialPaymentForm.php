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
            Section::make('Informações do Pagamento')->schema([
                Textarea::make('description')->label('Descrição')->required()->maxLength(65535)->columnSpanFull(),
                Select::make('type')->label('Tipo')->required()->options(['debit' => 'Saída (Pagamento)', 'credit' => 'Entrada (Recebimento)'])->default('debit'),
                Select::make('bank_account_id')->label('Conta Bancária')->relationship('bankAccount', 'name')->searchable()->preload()->required(),
                Select::make('payment_method_id')->label('Método de Pagamento')->relationship('paymentMethod', 'name')->searchable()->preload()->required(),
                DatePicker::make('payment_date')->label('Data do Pagamento')->required()->default(now()),
            ])->columns(2),
            Section::make('Valores')->schema([
                TextInput::make('amount')->label('Valor')->required()->numeric()->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? 'R$'),
                Select::make('currency_id')->label('Moeda')->relationship('currency', 'code')->searchable()->preload()->required()->live()->afterStateUpdated(function ($state, callable $set) {
                    if (!$state) return;
                    $baseCurrency = Currency::where('is_base', true)->first();
                    if (!$baseCurrency) return;
                    $rate = ExchangeRate::getConversionRate($state, $baseCurrency->id, now()->toDateString());
                    $set('exchange_rate_to_base', $rate ?? 1.0);
                }),
                TextInput::make('fee')->label('Taxas')->numeric()->default(0)->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? 'R$'),
                TextInput::make('exchange_rate_to_base')->label('Taxa de Câmbio')->numeric()->disabled()->dehydrated(),
            ])->columns(2),
        ]);
    }
}
