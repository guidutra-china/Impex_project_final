<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Repositories\FinancialTransactionRepository;
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

    protected function getRepository(): FinancialTransactionRepository
    {
        return app(FinancialTransactionRepository::class);
    }

    public function table(Table $table): Table
    {
        if (!$this->record instanceof Order) {
            return $table->query(
$this->getRepository()->getModel()::query()->whereRaw('1 = 0')
            );
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
$this->getRepository()->getModel()::query()
                    ->where('transactionable_type', Order::class)
                    ->where('transactionable_id', $this->record->id)
                    ->where('type', 'expense')
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
                    ->money(fn ($record) => $record->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('amount_base_currency')
                    ->label('Amount (USD)')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->alignEnd()
                    ->description(fn ($record) => 
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
                    ->color(fn ($record) => 
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
                    ->url(fn ($record) => 
                        route('filament.admin.resources.financial-transactions.edit', ['record' => $record->id])
                    )
                    ->openUrlInNewTab(),
                
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            $this->repository->delete($record->id);
                            
                            Notification::make()
                                ->title('Expense deleted successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error deleting expense')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();

                            \Log::error('Erro ao deletar despesa', [
                                'id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
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
