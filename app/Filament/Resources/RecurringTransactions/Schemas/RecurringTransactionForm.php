<?php
namespace App\Filament\Resources\RecurringTransactions\Schemas;
use App\Models\Currency;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
class RecurringTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informações Básicas')->schema([
                TextInput::make('name')->label('Nome')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Descrição')->maxLength(65535)->columnSpanFull(),
                Select::make('type')->label('Tipo')->required()->options(['payable' => 'Conta a Pagar', 'receivable' => 'Conta a Receber'])->default('payable')->live(),
                Select::make('financial_category_id')->label('Categoria')->relationship('category', 'name')->searchable()->preload()->required(),
            ])->columns(2),
            Section::make('Valores')->schema([
                TextInput::make('amount')->label('Valor')->required()->numeric()->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? 'R$'),
                Select::make('currency_id')->label('Moeda')->relationship('currency', 'code')->searchable()->preload()->required(),
            ])->columns(2),
            Section::make('Recorrência')->schema([
                Select::make('frequency')->label('Frequência')->required()->options(['daily' => 'Diária', 'weekly' => 'Semanal', 'monthly' => 'Mensal', 'quarterly' => 'Trimestral', 'yearly' => 'Anual'])->default('monthly')->live(),
                TextInput::make('interval')->label('Intervalo')->numeric()->default(1)->minValue(1),
                DatePicker::make('start_date')->label('Data de Início')->required()->default(now()),
                DatePicker::make('next_due_date')->label('Próxima Data')->required()->default(now()),
            ])->columns(2),
            Section::make('Configurações')->schema([
                Toggle::make('is_active')->label('Ativa')->default(true),
                Toggle::make('auto_generate')->label('Gerar Automaticamente')->default(true),
            ])->columns(2),
        ]);
    }
}
