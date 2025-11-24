<?php
namespace App\Filament\Resources\RecurringTransactions\Tables;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
class RecurringTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            BadgeColumn::make('type')->label('Tipo')->formatStateUsing(fn (string $state): string => match ($state) {'payable' => 'A Pagar', 'receivable' => 'A Receber', default => $state})->colors(['danger' => 'payable', 'success' => 'receivable']),
            TextColumn::make('amount')->label('Valor')->money(fn ($record) => $record->currency->code, divideBy: 100)->sortable(),
            TextColumn::make('frequency')->label('Frequência')->formatStateUsing(fn (string $state): string => match ($state) {'daily' => 'Diária', 'weekly' => 'Semanal', 'monthly' => 'Mensal', 'quarterly' => 'Trimestral', 'yearly' => 'Anual', default => $state}),
            TextColumn::make('next_due_date')->label('Próxima Data')->date('d/m/Y')->sortable(),
            IconColumn::make('is_active')->label('Ativa')->boolean(),
        ])->filters([
            SelectFilter::make('type')->label('Tipo')->options(['payable' => 'A Pagar', 'receivable' => 'A Receber']),
            TernaryFilter::make('is_active')->label('Ativa'),
        ])->actions([EditAction::make()])->defaultSort('next_due_date');
    }
}
