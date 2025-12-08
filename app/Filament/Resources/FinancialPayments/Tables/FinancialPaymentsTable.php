<?php
namespace App\Filament\Resources\FinancialPayments\Tables;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
class FinancialPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('payment_number')->label('Número')->searchable()->sortable()->copyable(),
            BadgeColumn::make('type')->label('Tipo')->formatStateUsing(fn (string $state): string => match ($state) {'debit' => 'Saída', 'credit' => 'Entrada', default => $state})->colors(['danger' => 'debit', 'success' => 'credit']),
            TextColumn::make('payment_date')->label('Data')->date('d/m/Y')->sortable(),
            TextColumn::make('amount')->label('Valor')->money(fn ($record) => $record->currency->code, divideBy: 100)->sortable(),
            TextColumn::make('bankAccount.name')->label('Conta Bancária')->searchable(),
            BadgeColumn::make('status')->label(__('fields.status'))->formatStateUsing(fn (string $state): string => match ($state) {'pending' => 'Pendente', 'completed' => 'Concluído', default => $state})->colors(['secondary' => 'pending', 'success' => 'completed']),
        ])->filters([
            SelectFilter::make('type')->label('Tipo')->options(['debit' => 'Saída', 'credit' => 'Entrada']),
        ])->actions([EditAction::make()])->defaultSort('payment_date', 'desc');
    }
}
