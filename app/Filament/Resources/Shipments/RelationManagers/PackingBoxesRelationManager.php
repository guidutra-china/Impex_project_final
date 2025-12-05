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
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
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
                    ->modalContent(function ($record) {
                        $items = $record->packingBoxItems;
                        $html = '<div class="space-y-4">';
                        
                        // Box Summary
                        $html .= '<div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">';
                        $html .= '<h3 class="text-lg font-semibold mb-2">Box Summary</h3>';
                        $html .= '<div class="grid grid-cols-4 gap-4 text-sm">';
                        $html .= '<div><span class="font-medium">Type:</span> ' . ucfirst($record->box_type) . '</div>';
                        $html .= '<div><span class="font-medium">Status:</span> ' . ucfirst(str_replace('_', ' ', $record->packing_status)) . '</div>';
                        $html .= '<div><span class="font-medium">Items:</span> ' . $record->total_items . '</div>';
                        $html .= '<div><span class="font-medium">Quantity:</span> ' . $record->total_quantity . ' units</div>';
                        $html .= '<div><span class="font-medium">Net Weight:</span> ' . number_format($record->net_weight, 2) . ' kg</div>';
                        $html .= '<div><span class="font-medium">Gross Weight:</span> ' . number_format($record->gross_weight, 2) . ' kg</div>';
                        $html .= '<div><span class="font-medium">Volume:</span> ' . number_format($record->volume, 4) . ' m³</div>';
                        $html .= '<div><span class="font-medium">Dimensions:</span> ' . $record->length . '×' . $record->width . '×' . $record->height . ' cm</div>';
                        $html .= '</div></div>';
                        
                        // Items Table
                        if ($items->count() > 0) {
                            $html .= '<div class="overflow-x-auto">';
                            $html .= '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">';
                            $html .= '<thead class="bg-gray-50 dark:bg-gray-800"><tr>';
                            $html .= '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>';
                            $html .= '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>';
                            $html .= '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>';
                            $html .= '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Wt (kg)</th>';
                            $html .= '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Wt (kg)</th>';
                            $html .= '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Vol (m³)</th>';
                            $html .= '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Vol (m³)</th>';
                            $html .= '</tr></thead><tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">';
                            
                            foreach ($items as $item) {
                                $html .= '<tr>';
                                $html .= '<td class="px-4 py-2 text-sm">' . $item->shipmentItem->product_sku . '</td>';
                                $html .= '<td class="px-4 py-2 text-sm">' . $item->shipmentItem->product_name . '</td>';
                                $html .= '<td class="px-4 py-2 text-sm text-right">' . $item->quantity . '</td>';
                                $html .= '<td class="px-4 py-2 text-sm text-right">' . number_format($item->unit_weight, 2) . '</td>';
                                $html .= '<td class="px-4 py-2 text-sm text-right font-semibold">' . number_format($item->quantity * $item->unit_weight, 2) . '</td>';
                                $html .= '<td class="px-4 py-2 text-sm text-right">' . number_format($item->unit_volume, 4) . '</td>';
                                $html .= '<td class="px-4 py-2 text-sm text-right font-semibold">' . number_format($item->quantity * $item->unit_volume, 4) . '</td>';
                                $html .= '</tr>';
                            }
                            
                            $html .= '</tbody></table></div>';
                        } else {
                            $html .= '<div class="text-center py-8 text-gray-500">No items in this box yet.</div>';
                        }
                        
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);
                    }),
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
