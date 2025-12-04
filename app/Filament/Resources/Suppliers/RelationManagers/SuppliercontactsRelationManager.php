<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\SupplierContacts\Schemas\SupplierContactForm;
use App\Filament\Resources\SupplierContacts\Tables\SupplierContactsTable;
use App\Repositories\SupplierRepository;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class SuppliercontactsRelationManager extends RelationManager
{
    protected static string $relationship = 'suppliercontacts';

    protected static ?string $title = 'Contacts';

    protected SupplierRepository $repository;

    public function mount(): void {
        parent::mount();
        $this->repository = app(SupplierRepository::class);
    }

    public function form(Schema $schema): Schema
    {
        return SupplierContactForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return SupplierContactsTable::configure($table)
            ->query(
                $this->repository->getContactsQuery($this->getOwnerRecord()->id)
            )
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
