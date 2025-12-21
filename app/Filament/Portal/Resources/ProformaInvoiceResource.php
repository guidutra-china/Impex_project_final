<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ProformaInvoiceResource\Pages;
use App\Models\ProformaInvoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class ProformaInvoiceResource extends Resource
{
    protected static ?string $model = ProformaInvoice::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        // Allow all portal users to access their own PIs (filtered by ClientOwnershipScope)
        return true;
    }

    // Multi-tenancy filtering is handled automatically by ClientOwnershipScope global scope

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Proforma Invoice Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('proforma_number')
                                    ->label('Proforma Number')
                                    ->disabled()
                                    ->columnSpan(1),
                                TextInput::make('revision_number')
                                    ->label('Revision')
                                    ->disabled()
                                    ->columnSpan(1),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->disabled()
                                    ->columnSpan(1),
                                DatePicker::make('issue_date')
                                    ->label('Issue Date')
                                    ->disabled()
                                    ->columnSpan(1),
                                DatePicker::make('valid_until')
                                    ->label('Valid Until')
                                    ->disabled()
                                    ->columnSpan(1),
                                Placeholder::make('total_formatted')
                                    ->label('Total Amount')
                                    ->content(fn ($record) => $record ? $record->currency->symbol . ' ' . number_format($record->total, 2) : '-')
                                    ->columnSpan(1),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                TextInput::make('product_name')
                                    ->label('Product')
                                    ->disabled()
                                    ->columnSpan(2),
                                TextInput::make('notes')
                                    ->label('Description')
                                    ->disabled()
                                    ->columnSpan(2),
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->disabled()
                                    ->columnSpan(1),
                                Placeholder::make('unit_price_formatted')
                                    ->label('Unit Price')
                                    ->content(fn ($record, $get) => {
                                        $pi = $record?->proformaInvoice ?? $get('../../');
                                        $currency = $pi?->currency?->symbol ?? '$';
                                        return $currency . ' ' . number_format($record?->unit_price ?? 0, 2);
                                    })
                                    ->columnSpan(1),
                                Placeholder::make('total_formatted')
                                    ->label('Total')
                                    ->content(fn ($record, $get) => {
                                        $pi = $record?->proformaInvoice ?? $get('../../');
                                        $currency = $pi?->currency?->symbol ?? '$';
                                        $total = $record?->total ?? ($record?->quantity * $record?->unit_price ?? 0);
                                        return $currency . ' ' . number_format($total, 2);
                                    })
                                    ->columnSpan(1),
                            ])
                            ->columns(7)
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proforma_number')
                    ->label('Proforma #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('revision_number')
                    ->label('Rev.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Issue Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\IconColumn::make('deposit_required')
                    ->label('Deposit')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
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
            'index' => Pages\ListProformaInvoices::route('/'),
            'view' => Pages\ViewProformaInvoice::route('/{record}'),
        ];
    }
}
