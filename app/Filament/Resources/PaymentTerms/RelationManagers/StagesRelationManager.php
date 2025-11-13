<?php

namespace App\Filament\Resources\PaymentTerm\RelationManagers;

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
                    ->columnSpan(1),
                TextInput::make('days_from_invoice')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('days')
                    ->columnSpan(1),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(1)
                    ->columnSpan(1),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('percentage')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('percentage')
                    ->label('Percentage')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('days_from_invoice')
                    ->label('Due (Days from Invoice)')
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
