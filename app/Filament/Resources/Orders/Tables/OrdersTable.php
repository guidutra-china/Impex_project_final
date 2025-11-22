<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('order_number')
                    ->label('RFQ Number')
                    ->searchable()
                    ->sortable(),

               TextColumn::make('customer_nr_rfq')
                    ->label('Customer Ref.')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

               TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),

               TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->toggleable()
                    ->placeholder('No tags'),

               BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'info' => 'quoted',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),

               TextColumn::make('currency.code')
                    ->label('Currency')
                    ->sortable(),

               TextColumn::make('commission_percent')
                    ->label('Commission')
                    ->suffix('%')
                    ->sortable(),

               TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                    ->sortable(),

               TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'quoted' => 'Quoted',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer'),

                SelectFilter::make('currency_id')
                    ->relationship('currency', 'code')
                    ->label('Currency'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('view_comparison')
                    ->label('Compare Quotes')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Order $record): string => route('filament.admin.pages.quote-comparison', ['order' => $record->id]))
                    ->visible(fn (Order $record) => $record->supplierQuotes()->count() > 0),
                
                Action::make('request_quotes_bulk')
                    ->label('Request Quotes')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->form([
                        Select::make('suppliers')
                            ->label('Select Suppliers')
                            ->multiple()
                            ->options(Supplier::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select multiple suppliers to request quotes from'),
                        
                        Textarea::make('message')
                            ->label('Message')
                            ->default('Please provide your best quote for the attached RFQ.')
                            ->rows(3)
                            ->helperText('This message will be included in the email to suppliers'),
                    ])
                    ->action(function (Order $record, array $data) {
                        $count = 0;
                        
                        foreach ($data['suppliers'] as $supplierId) {
                            // Create supplier quote if not exists
                            $existingQuote = SupplierQuote::where('order_id', $record->id)
                                ->where('supplier_id', $supplierId)
                                ->first();
                            
                            if (!$existingQuote) {
                                SupplierQuote::create([
                                    'order_id' => $record->id,
                                    'supplier_id' => $supplierId,
                                    'status' => 'draft',
                                    'currency_id' => $record->currency_id,
                                ]);
                                
                                $count++;
                            }
                            
                            // TODO: Send email to supplier
                            // Mail::to(Supplier::find($supplierId)->email)
                            //     ->send(new QuoteRequestMail($record, $data['message']));
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Quote requests created')
                            ->body("Created {$count} quote requests for " . count($data['suppliers']) . " suppliers.")
                            ->send();
                    })
                    ->visible(fn (Order $record) => in_array($record->status, ['pending', 'processing'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}