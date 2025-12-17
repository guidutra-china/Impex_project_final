<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\CustomerQuoteResource\Pages;
use App\Models\CustomerQuote;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class CustomerQuoteResource extends Resource
{
    protected static ?string $model = CustomerQuote::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('purchasing');
    }

    // Multi-tenancy filtering is handled automatically by ClientOwnershipScope global scope
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'order' => function ($query) {
                // Load order without its own scope to avoid conflicts
                $query->withoutGlobalScopes();
            },
            'items.supplierQuote' => function ($query) {
                // Load supplier quotes without scope to avoid conflicts
                $query->withoutGlobalScopes()->with(['supplier', 'items.product']);
            }
        ]);
    }

    // Form not needed - using custom view for display

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_number')
                    ->label('Quote #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('RFQ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'warning' => 'viewed',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                        'gray' => 'expired',
                    ]),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Options')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'viewed' => 'Viewed',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerQuotes::route('/'),
            'view' => Pages\ViewCustomerQuote::route('/{record}'),
        ];
    }
}
