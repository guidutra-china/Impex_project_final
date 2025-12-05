<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use App\Services\Shipment\PackingService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use BackedEnum;

class PackingBoxesRelationManager extends RelationManager
{
    protected static string $relationship = 'packingBoxes';

    protected static ?string $title = 'Packing Boxes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'box_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Box Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('box_number')
                                    ->label('Box Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated'),

                                Select::make('box_type')
                                    ->label('Box Type')
                                    ->options([
                                        'carton' => 'Carton',
                                        'wooden_crate' => 'Wooden Crate',
                                        'pallet' => 'Pallet',
                                        'drum' => 'Drum',
                                        'bag' => 'Bag',
                                        'other' => 'Other',
                                    ])
                                    ->default('carton')
                                    ->required(),

                                TextInput::make('box_label')
                                    ->label('Box Label')
                                    ->placeholder('e.g., Box A, Crate 1'),
                            ]),
                    ]),

                Section::make('Dimensions (cm)')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('length')
                                    ->label('Length (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('cm'),

                                TextInput::make('width')
                                    ->label('Width (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('cm'),

                                TextInput::make('height')
                                    ->label('Height (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('cm'),
                            ]),
                    ]),

                Section::make('Weight')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('gross_weight')
                                    ->label('Gross Weight (kg)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('kg')
                                    ->helperText('Total weight including packaging'),

                                TextInput::make('net_weight')
                                    ->label('Net Weight (kg)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('kg')
                                    ->helperText('Weight of contents only')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('box_number')
                    ->label('Box #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('box_label')
                    ->label('Label')
                    ->searchable()
                    ->default('-'),

                BadgeColumn::make('box_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->colors([
                        'primary' => 'carton',
                        'warning' => 'wooden_crate',
                        'info' => 'pallet',
                        'success' => 'drum',
                    ]),

                BadgeColumn::make('packing_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->colors([
                        'secondary' => 'empty',
                        'warning' => 'packing',
                        'success' => 'sealed',
                    ]),

                TextColumn::make('total_items')
                    ->label('Items')
                    ->alignCenter()
                    ->default(0)
                    ->badge()
                    ->color('primary'),

                TextColumn::make('total_quantity')
                    ->label('Quantity')
                    ->alignCenter()
                    ->default(0)
                    ->badge()
                    ->color('success'),

                TextColumn::make('dimensions')
                    ->label('Dimensions (L×W×H cm)')
                    ->formatStateUsing(function ($record) {
                        if ($record->length && $record->width && $record->height) {
                            return "{$record->length}×{$record->width}×{$record->height}";
                        }
                        return '-';
                    })
                    ->toggleable(),

                TextColumn::make('volume')
                    ->label('Volume (m³)')
                    ->numeric(3)
                    ->alignEnd()
                    ->default(0)
                    ->toggleable(),

                TextColumn::make('gross_weight')
                    ->label('Gross Wt (kg)')
                    ->numeric(2)
                    ->alignEnd()
                    ->default(0),

                TextColumn::make('net_weight')
                    ->label('Net Wt (kg)')
                    ->numeric(2)
                    ->alignEnd()
                    ->default(0)
                    ->toggleable(),

                TextColumn::make('sealed_at')
                    ->label('Sealed At')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('sealedBy.name')
                    ->label('Sealed By')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create Box')
                    ->color('success')
                    ->icon(Heroicon::OutlinedCube)
                    ->using(function (array $data, $livewire) {
                        $shipment = $livewire->getOwnerRecord();
                        $service = new PackingService();
                        
                        return $service->createBox($shipment, $data);
                    })
                    ->successNotificationTitle('Box created successfully'),

                Action::make('auto_pack')
                    ->label('Auto-Pack Items')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('success')
                    ->form([
                        TextInput::make('number_of_boxes')
                            ->label('Number of Boxes')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->helperText('Items will be distributed evenly across boxes'),
                    ])
                    ->action(function (array $data, $livewire) {
                        $shipment = $livewire->getOwnerRecord();
                        $service = new PackingService();
                        
                        try {
                            $service->autoPackItems($shipment, $data['number_of_boxes']);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Auto-pack completed')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Auto-pack failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
            ])
             ->recordActions([
                Action::make('viewItems')
                    ->label('View Items')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('info')
                    ->modalHeading(fn ($record) => 'Items in Box #' . $record->box_number)
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Box Items')
                            ->schema([
                                \Filament\Infolists\Components\RepeatableEntry::make('packingBoxItems')
                                    ->label('')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('shipmentItem.product_sku')
                                            ->label('SKU'),
                                        \Filament\Infolists\Components\TextEntry::make('shipmentItem.product_name')
                                            ->label('Product'),
                                        \Filament\Infolists\Components\TextEntry::make('quantity')
                                            ->label('Quantity')
                                            ->numeric(),
                                        \Filament\Infolists\Components\TextEntry::make('unit_weight')
                                            ->label('Unit Weight')
                                            ->suffix(' kg')
                                            ->numeric(decimalPlaces: 2),
                                        \Filament\Infolists\Components\TextEntry::make('total_weight')
                                            ->label('Total Weight')
                                            ->suffix(' kg')
                                            ->state(fn ($record) => $record->quantity * $record->unit_weight)
                                            ->numeric(decimalPlaces: 2)
                                            ->weight('bold'),
                                        \Filament\Infolists\Components\TextEntry::make('unit_volume')
                                            ->label('Unit Volume')
                                            ->suffix(' m³')
                                            ->numeric(decimalPlaces: 4),
                                        \Filament\Infolists\Components\TextEntry::make('total_volume')
                                            ->label('Total Volume')
                                            ->suffix(' m³')
                                            ->state(fn ($record) => $record->quantity * $record->unit_volume)
                                            ->numeric(decimalPlaces: 4)
                                            ->weight('bold'),
                                    ])
                                    ->columns(7)
                                    ->grid(7),
                            ]),
                        \Filament\Infolists\Components\Section::make('Box Summary')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('box_type')
                                    ->label('Box Type')
                                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                                \Filament\Infolists\Components\TextEntry::make('packing_status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                                \Filament\Infolists\Components\TextEntry::make('total_items')
                                    ->label('Total Items'),
                                \Filament\Infolists\Components\TextEntry::make('total_quantity')
                                    ->label('Total Quantity')
                                    ->suffix(' units')
                                    ->numeric(),
                                \Filament\Infolists\Components\TextEntry::make('net_weight')
                                    ->label('Net Weight')
                                    ->suffix(' kg')
                                    ->numeric(decimalPlaces: 2)
                                    ->weight('bold')
                                    ->size('lg'),
                                \Filament\Infolists\Components\TextEntry::make('gross_weight')
                                    ->label('Gross Weight')
                                    ->suffix(' kg')
                                    ->numeric(decimalPlaces: 2)
                                    ->weight('bold')
                                    ->size('lg'),
                                \Filament\Infolists\Components\TextEntry::make('volume')
                                    ->label('Volume')
                                    ->suffix(' m³')
                                    ->numeric(decimalPlaces: 4)
                                    ->weight('bold')
                                    ->size('lg'),
                                \Filament\Infolists\Components\TextEntry::make('dimensions')
                                    ->label('Dimensions (L×W×H)')
                                    ->state(fn ($record) => $record->length . ' × ' . $record->width . ' × ' . $record->height . ' cm'),
                            ])
                            ->columns(4),
                    ]),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->using(function ($record) {
                        $service = new PackingService();
                        $service->deleteBox($record);
                    })
                    ->successNotificationTitle('Box deleted successfully'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('No packing boxes created')
            ->emptyStateDescription('Create boxes or use auto-pack to organize items for shipment.')
            ->emptyStateIcon(Heroicon::OutlinedArchiveBox);
    }
}
