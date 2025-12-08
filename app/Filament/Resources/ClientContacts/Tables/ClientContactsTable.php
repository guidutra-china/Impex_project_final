<?php

namespace App\Filament\Resources\ClientContacts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('fields.phone'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('wechat')
                    ->label('WeChat Id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('function')
                    ->label('Function')
                    ->badge()        // ← Added badge display
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
