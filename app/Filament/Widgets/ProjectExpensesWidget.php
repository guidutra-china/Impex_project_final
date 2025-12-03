<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use App\Models\Order;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class ProjectExpensesWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 50;

    public function table(Table $table): Table
    {
        if (!$this->record instanceof Order) {
            return $table->query(FinancialTransaction::query()->whereRaw('1 = 0'));
        }

        // Get totals (now correctly using amount_base_currency)
        $totalExpenses = $this->record->total_project_expenses_dollars ?? 0;
        $realMargin = $this->record->real_margin ?? 0;
        $realMarginPercent = $this->record->real_margin_percent ?? 0;

        return $table
            ->heading('Project Expenses')
            ->description(sprintf(
                'Total Expenses: $%s USD | Real Margin: $%s (%.2f%%)',
                number_format($totalExpenses, 2),
                number_format($realMargin, 2),
                $realMarginPercent
            ))
            ->query(
                FinancialTransaction::query()
                    ->where('project_id', $this->record->id)
                    ->where('type', 'payable')
                    ->with(['category', 'currency', 'creator'])
                    ->orderBy('transaction_date', 'desc')
            )
            ->columns([
                TextColumn::make('transaction_number')
                    ->label('Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Transaction number copied')
                    ->copyMessageDuration(1500),
                
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 30) {
                            return $state;
                        }
                        return null;
                    }),
                
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }
                        return null;
                    }),
                
                TextColumn::make('amount')
                    ->label('Amount (Original)')
                    ->money(fn (FinancialTransaction $record): string => $record->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('amount_base_currency')
                    ->label('Amount (USD)')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->alignEnd()
                    ->description(fn (FinancialTransaction $record): string => 
                        $record->exchange_rate_to_base 
                            ? sprintf('Rate: %s', number_format($record->exchange_rate_to_base, 4))
                            : ''
                    ),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partially_paid' => 'warning',
                        'pending' => 'info',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn (FinancialTransaction $record): string => 
                        $record->isOverdue() ? 'danger' : 'gray'
                    ),
                
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (FinancialTransaction $record): string => 
                        route('filament.admin.resources.financial-transactions.edit', ['record' => $record->id])
                    )
                    ->openUrlInNewTab(),
                
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (FinancialTransaction $record) {
                        $record->delete();
                        
                        Notification::make()
                            ->title('Expense deleted successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No project expenses yet')
            ->emptyStateDescription('Add expenses related to this RFQ using the "Add Project Expense" button above.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    public static function canView(): bool
    {
        return true;
    }
}
