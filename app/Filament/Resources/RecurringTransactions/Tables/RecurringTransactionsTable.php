<?php
namespace App\Filament\Resources\RecurringTransactions\Tables;
use Filament\Tables;
use Filament\Tables\Table;
class RecurringTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            Tables\Columns\BadgeColumn::make('type')->label('Tipo')->formatStateUsing(fn (string $state): string => match ($state) {'payable' => 'A Pagar', 'receivable' => 'A Receber', default => $state})->colors(['danger' => 'payable', 'success' => 'receivable']),
            Tables\Columns\TextColumn::make('amount')->label('Valor')->money(fn ($record) => $record->currency->code, divideBy: 100)->sortable(),
            Tables\Columns\TextColumn::make('frequency')->label('Frequência')->formatStateUsing(fn (string $state): string => match ($state) {'daily' => 'Diária', 'weekly' => 'Semanal', 'monthly' => 'Mensal', 'quarterly' => 'Trimestral', 'yearly' => 'Anual', default => $state}),
            Tables\Columns\TextColumn::make('next_due_date')->label('Próxima Data')->date('d/m/Y')->sortable(),
            Tables\Columns\IconColumn::make('is_active')->label('Ativa')->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')->label('Tipo')->options(['payable' => 'A Pagar', 'receivable' => 'A Receber']),
            Tables\Filters\TernaryFilter::make('is_active')->label('Ativa'),
        ])->actions([Tables\Actions\EditAction::make()])->defaultSort('next_due_date');
    }
}
