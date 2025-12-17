<?php

namespace App\Filament\Resources\CustomerQuotes\Tables;

use App\Models\CustomerQuote;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerQuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->label('Quote Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.order_number')
                    ->label('RFQ')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.edit', $record->order) : null),

                TextColumn::make('order.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'warning' => 'viewed',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                        'gray' => 'expired',
                    ]),

                TextColumn::make('items_count')
                    ->label('Options')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at < now() ? 'danger' : null),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not sent'),

                TextColumn::make('viewed_at')
                    ->label('Viewed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not viewed'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'viewed' => 'Viewed',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ])
                    ->multiple()
                    ->label('Status'),

                SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->label('RFQ'),

                TernaryFilter::make('expired')
                    ->label('Expired Status')
                    ->placeholder('All quotes')
                    ->trueLabel('Expired only')
                    ->falseLabel('Not expired only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('expires_at', '<', now()),
                        false: fn (Builder $query) => $query->where('expires_at', '>=', now()),
                    ),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.customer-quotes.view', $record)),
                
                EditAction::make(),
                
                Action::make('copy_link')
                    ->label('Copy Public Link')
                    ->icon('heroicon-o-link')
                    ->action(function ($record) {
                        $url = route('public.customer-quote.show', ['token' => $record->public_token]);
                        // Copy to clipboard would be handled by frontend JS
                        \Filament\Notifications\Notification::make()
                            ->title('Public Link')
                            ->body($url)
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
