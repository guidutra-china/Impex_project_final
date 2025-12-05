<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Resources\ClientContacts\Schemas\ClientContactForm;
use App\Filament\Resources\ClientContacts\Tables\ClientContactsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ClientcontactsRelationManager extends RelationManager
{
    protected static string $relationship = 'clientcontacts';

    public function form(Schema $schema): Schema
    {
        return ClientContactForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return ClientContactsTable::configure($table)
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
            ]);
    }
}
