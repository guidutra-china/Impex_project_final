<?php

namespace App\Filament\Resources\FinancialPayments\RelationManagers;

use App\Repositories\FinancialTransactionRepository;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class AllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    protected static ?string $title = 'Payment Allocations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'id';

    protected FinancialTransactionRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = app(FinancialTransactionRepository::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction.transaction_number')
                    ->label('Transaction #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('transaction.description')
                    ->label('Description')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('transaction.type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'payable' => 'Payable',
                        'receivable' => 'Receivable',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'payable' => 'danger',
                        'receivable' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('allocated_amount')
                    ->label('Allocated Amount')
                    ->formatStateUsing(function ($state, $record) {
                        $currency = $record->transaction->currency;
                        return money($state, $currency->code);
                    })
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('gain_loss_on_exchange')
                    ->label('Exchange Gain/Loss')
                    ->formatStateUsing(function ($state) {
                        if ($state == 0) return '—';
                        $symbol = $state > 0 ? '+' : '';
                        return $symbol . money(abs($state), 'USD');
                    })
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->weight('medium'),

                TextColumn::make('allocation_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'automatic' ? 'info' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Allocate to Transaction')
                    ->modalHeading('Allocate Payment to Transaction')
                    ->modalDescription('Allocate this payment to a financial transaction (invoice, bill, etc.)')
                    ->form([
                        Select::make('financial_transaction_id')
                            ->label('Transaction')
                            ->options(function ($livewire) {
                                $payment = $livewire->ownerRecord;
                                
                                // Get pending or partially paid transactions of the same type
                                $type = $payment->type === 'debit' ? 'payable' : 'receivable';
                                
                                return $this->repository->getPendingTransactionsForAllocation($type)
                                    ->get()
                                    ->mapWithKeys(function ($transaction) {
                                        $remaining = ($transaction->amount - $transaction->paid_amount) / 100;
                                        $currency = $transaction->currency->code;
                                        $status = $transaction->status === 'overdue' ? '⚠️ OVERDUE' : strtoupper($transaction->status);
                                        return [
                                            $transaction->id => "[{$status}] {$transaction->transaction_number} - {$transaction->description} (Remaining: {$currency} {$remaining})"
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->helperText('Select a transaction to allocate this payment to'),

                        TextInput::make('allocated_amount')
                            ->label('Amount to Allocate')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->helperText(function ($livewire) {
                                $payment = $livewire->ownerRecord;
                                $unallocated = $payment->unallocated_amount / 100;
                                return "Available to allocate: $" . number_format($unallocated, 2);
                            })
                            ->rules([
                                function ($livewire) {
                                    return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                        $payment = $livewire->ownerRecord;
                                        $valueInCents = $value * 100;
                                        
                                        if ($valueInCents > $payment->unallocated_amount) {
                                            $fail('Amount exceeds available unallocated amount.');
                                        }
                                    };
                                },
                            ]),

                        Select::make('allocation_type')
                            ->label('Allocation Type')
                            ->options([
                                'manual' => 'Manual',
                                'automatic' => 'Automatic',
                            ])
                            ->default('manual')
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(65535)
                            ->rows(3)
                            ->placeholder('Optional notes about this allocation...'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        // Convert amount to cents
                        $data['allocated_amount'] = (int) ($data['allocated_amount'] * 100);
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        TextInput::make('allocated_amount')
                            ->label('Allocated Amount')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->formatStateUsing(fn ($state) => $state / 100)
                            ->dehydrateStateUsing(fn ($state) => (int) ($state * 100)),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(65535)
                            ->rows(3),
                    ]),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No allocations yet')
            ->emptyStateDescription('Allocate this payment to one or more transactions')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
