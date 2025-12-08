<?php

namespace App\Filament\Resources\PaymentTerms\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class StagesRelationManager extends RelationManager
{
    protected static string $relationship = 'stages';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('percentage')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%')
                    ->label('Payment Percentage')
                    ->columnSpan(1),
                
                TextInput::make('days')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('days')
                    ->label('Days')
                    ->helperText('Number of days from the calculation base')
                    ->columnSpan(1),
                
                Select::make('calculation_base')
                    ->required()
                    ->options([
                        'invoice_date' => 'Invoice Date',
                        'shipment_date' => 'Shipment Date',
                    ])
                    ->default('invoice_date')
                    ->label('Calculate From')
                    ->helperText('Base date for calculating the due date')
                    ->columnSpan(1),
                
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(1)
                    ->label(__('fields.sort_order'))
                    ->helperText('Stage order (1, 2, 3...)')
                    ->columnSpan(1),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('percentage')
            ->columns([
                TextColumn::make('sort_order')
                    ->label(__('fields.sort_order'))
                    ->sortable(),
                
                TextColumn::make('percentage')
                    ->label('Payment %')
                    ->suffix('%')
                    ->sortable(),
                
                TextColumn::make('days')
                    ->label('Days')
                    ->sortable(),
                
                TextColumn::make('calculation_base')
                    ->label('Calculate From')
                    ->badge()
                    ->colors([
                        'primary' => 'invoice_date',
                        'success' => 'shipment_date',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'invoice_date' => 'Invoice Date',
                        'shipment_date' => 'Shipment Date',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }
}
