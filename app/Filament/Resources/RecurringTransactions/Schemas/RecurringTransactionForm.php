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
            Section::make('Basic Information')->schema([
                TextInput::make('name')->label(__('fields.name'))->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label(__('fields.description'))->maxLength(65535)->columnSpanFull(),
                Select::make('type')->label(__('fields.type'))->required()->options(['payable' => 'Payable', 'receivable' => 'Receivable'])->default('payable')->live(),
                Select::make('financial_category_id')->label(__('fields.category'))->relationship('category', 'name')->searchable()->preload()->required(),
            ])->columns(2),
            Section::make('Values')->schema([
                TextInput::make('amount')->label(__('fields.amount'))->required()->numeric()->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? '$'),
                Select::make('currency_id')->label(__('fields.currency'))->relationship('currency', 'code')->searchable()->preload()->required(),
            ])->columns(2),
            Section::make('Recurrence')->schema([
                Select::make('frequency')->label('Frequency')->required()->options(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'])->default('monthly')->live(),
                TextInput::make('interval')->label('Interval')->numeric()->default(1)->minValue(1),
                DatePicker::make('start_date')->label(__('fields.start_date'))->required()->default(now()),
                DatePicker::make('next_due_date')->label('Next Due Date')->required()->default(now()),
            ])->columns(2),
            Section::make('Settings')->schema([
                Toggle::make('is_active')->label(__('common.active'))->default(true),
                Toggle::make('auto_generate')->label('Auto Generate')->default(true),
            ])->columns(2),
        ]);
    }
}
