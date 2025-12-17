<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('purchasing');
    }

    public static function getEloquentQuery(): Builder
    {
        // Filter by client_id through proforma_invoice
        return parent::getEloquentQuery()
            ->whereHas('proformaInvoice.order', function ($query) {
                $query->where('client_id', auth()->user()->client_id);
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Purchase Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('po_number')
                            ->label('PO Number')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'confirmed' => 'Confirmed',
                                'in_production' => 'In Production',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('supplier.name')
                            ->label('Supplier')
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplierQuote.quote_number')
                    ->label('Quote #')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'warning' => 'confirmed',
                        'primary' => 'in_production',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency ?? 'USD')
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
                        'confirmed' => 'Confirmed',
                        'in_production' => 'In Production',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
        ];
    }
}
