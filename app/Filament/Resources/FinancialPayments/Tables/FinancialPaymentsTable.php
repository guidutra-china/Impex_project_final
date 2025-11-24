<?php
namespace App\Filament\Resources\FinancialPayments\Tables;
use Filament\Tables;
use Filament\Tables\Table;
class FinancialPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('payment_number')->label('Número')->searchable()->sortable()->copyable(),
            Tables\Columns\BadgeColumn::make('type')->label('Tipo')->formatStateUsing(fn (string $state): string => match ($state) {'debit' => 'Saída', 'credit' => 'Entrada', default => $state})->colors(['danger' => 'debit', 'success' => 'credit']),
            Tables\Columns\TextColumn::make('payment_date')->label('Data')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('amount')->label('Valor')->money(fn ($record) => $record->currency->code, divideBy: 100)->sortable(),
            Tables\Columns\TextColumn::make('bankAccount.name')->label('Conta Bancária')->searchable(),
            Tables\Columns\BadgeColumn::make('status')->label('Status')->formatStateUsing(fn (string $state): string => match ($state) {'pending' => 'Pendente', 'completed' => 'Concluído', default => $state})->colors(['secondary' => 'pending', 'success' => 'completed']),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')->label('Tipo')->options(['debit' => 'Saída', 'credit' => 'Entrada']),
        ])->actions([Tables\Actions\EditAction::make()])->defaultSort('payment_date', 'desc');
    }
}
