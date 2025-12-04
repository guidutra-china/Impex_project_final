<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use App\Models\ShipmentContainer;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Actions\SealContainerAction;
use App\Filament\Actions\UnsealContainerAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class ShipmentContainersRelationManager extends RelationManager
{
    protected static string $relationship = 'containers';

    protected static ?string $title = 'Shipment Containers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'container_number';

    public function mount(): void
    {
        parent::mount();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Container Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('container_number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., MSCU1234567'),

                                Select::make('container_type')
                                    ->options([
                                        '20ft' => '20ft',
                                        '40ft' => '40ft',
                                        '40hc' => '40hc',
                                        'pallet' => 'Pallet',
                                        'box' => 'Box',
                                    ])
                                    ->required()
                                    ->default('40ft'),

                                TextInput::make('max_weight')
                                    ->numeric()
                                    ->required()
                                    ->suffix('kg'),

                                TextInput::make('max_volume')
                                    ->numeric()
                                    ->required()
                                    ->suffix('m³'),

                                TextInput::make('current_weight')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('kg'),

                                TextInput::make('current_volume')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('m³'),

                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'packed' => 'Packed',
                                        'sealed' => 'Sealed',
                                        'in_transit' => 'In Transit',
                                        'delivered' => 'Delivered',
                                    ])
                                    ->required()
                                    ->default('draft'),

                                TextInput::make('seal_number')
                                    ->placeholder('e.g., SEAL123456')
                                    ->unique(ignoreRecord: true),
                            ]),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('container_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('container_type')
                    ->badge()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'packed',
                        'success' => 'sealed',
                        'warning' => 'in_transit',
                        'success' => 'delivered',
                    ])
                    ->sortable(),

                TextColumn::make('current_weight')
                    ->label('Weight')
                    ->formatStateUsing(fn($state, $record) => "{$state} / {$record->max_weight} kg")
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('current_volume')
                    ->label('Volume')
                    ->formatStateUsing(fn($state, $record) => "{$state} / {$record->max_volume} m³")
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->alignCenter(),

                TextColumn::make('seal_number')
                    ->label('Seal')
                    ->searchable()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Container')
                    ->color('success')
                    ->icon(Heroicon::OutlinedPlus)
                    ->before(function ($data) {
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->recordActions([
                SealContainerAction::make(),
                UnsealContainerAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('No containers added')
            ->emptyStateDescription('Add containers to this shipment to organize items.')
            ->emptyStateIcon(Heroicon::OutlinedArchiveBox);
    }
}
