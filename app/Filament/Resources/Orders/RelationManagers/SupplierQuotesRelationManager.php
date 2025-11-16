<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierQuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierQuotes';

    protected static ?string $title = 'Supplier Quotes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(1),

                Select::make('currency_id')
                    ->relationship('currency', 'code', fn ($query) => $query->where('is_active', true))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Quote Currency')
                    ->columnSpan(1),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ])
                    ->required()
                    ->default('draft')
                    ->columnSpan(1),

                TextInput::make('validity_days')
                    ->label('Valid for (days)')
                    ->numeric()
                    ->default(30)
                    ->minValue(1)
                    ->columnSpan(1),

                Textarea::make('supplier_notes')
                    ->label('Supplier Notes')
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Internal Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('quote_number')
            ->columns([
                TextColumn::make('quote_number')
                    ->searchable(),

                TextColumn::make('supplier.name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('currency.code')
                    ->label('Currency'),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ]),

                TextColumn::make('total_price_after_commission')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100),

                TextColumn::make('locked_exchange_rate')
                    ->label('Rate')
                    ->numeric(decimalPlaces: 4)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valid_until')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record) {
                        // Calculate commission after quote is created
                        $record->calculateCommission();
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('calculate_commission')
                    ->label('Recalculate')
                    ->icon('heroicon-o-calculator')
                    ->action(function ($record) {
                        $record->calculateCommission();
                    })
                    ->requiresConfirmation()
                    ->color('warning'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
