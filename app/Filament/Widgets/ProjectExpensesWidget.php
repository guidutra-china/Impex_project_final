<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use App\Models\Order;
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
        // Simple test: just show the widget is loading
        if (!$this->record) {
            \Log::info('ProjectExpensesWidget: No record provided');
        } else {
            \Log::info('ProjectExpensesWidget: Record provided', [
                'type' => get_class($this->record),
                'id' => $this->record->id ?? 'no-id'
            ]);
        }

        if (!$this->record instanceof Order) {
            return $table
                ->heading('⚠️ Project Expenses Widget - No Record')
                ->description('Widget is loading but no Order record was provided')
                ->query(FinancialTransaction::query()->whereRaw('1 = 0'))
                ->columns([
                    TextColumn::make('id')->label('ID'),
                ]);
        }

        // Get totals safely
        $totalExpenses = 0;
        $realMargin = 0;
        $realMarginPercent = 0;
        
        try {
            $totalExpenses = $this->record->total_project_expenses_dollars ?? 0;
            $realMargin = $this->record->real_margin ?? 0;
            $realMarginPercent = $this->record->real_margin_percent ?? 0;
            
            \Log::info('ProjectExpensesWidget: Totals calculated', [
                'total_expenses' => $totalExpenses,
                'real_margin' => $realMargin,
                'real_margin_percent' => $realMarginPercent
            ]);
        } catch (\Exception $e) {
            \Log::error('ProjectExpensesWidget: Error calculating totals', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $table
            ->heading('✅ Project Expenses')
            ->description(sprintf(
                'Total Expenses: $%s | Real Margin: $%s (%.2f%%) | RFQ: %s',
                number_format($totalExpenses, 2),
                number_format($realMargin, 2),
                $realMarginPercent,
                $this->record->order_number ?? 'N/A'
            ))
            ->query(
                FinancialTransaction::query()
                    ->where('project_id', $this->record->id)
                    ->where('type', 'payable')
                    ->with(['category', 'currency'])
                    ->orderBy('transaction_date', 'desc')
            )
            ->columns([
                TextColumn::make('transaction_number')
                    ->label('Number')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),
                
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn (FinancialTransaction $record): string => $record->currency->code ?? 'USD', divideBy: 100)
                    ->sortable(),
                
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
                    }),
                
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
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
