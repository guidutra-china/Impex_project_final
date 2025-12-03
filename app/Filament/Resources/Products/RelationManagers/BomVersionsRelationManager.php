<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\BomVersion;
use App\Repositories\ProductRepository;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class BomVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'bomVersions';

    protected static ?string $title = 'BOM Versions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected ProductRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = app(ProductRepository::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->repository->getBomVersionsQuery($this->getOwnerRecord()->id)
            )
            ->recordTitleAttribute('version_display')
            ->columns([
                TextColumn::make('version_number')
                    ->label('#')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('version_name')
                    ->label('Version')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'warning',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('total_manufacturing_cost_snapshot')
                    ->label('Total Cost')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('bomVersionItems_count')
                    ->label('Components')
                    ->counts('bomVersionItems')
                    ->badge()
                    ->color('info'),

                TextColumn::make('change_notes')
                    ->label('Changes')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->change_notes)
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->default('System')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('activated_at')
                    ->label('Activated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create Version')
                    ->modalHeading('Create New BOM Version')
                    ->modalDescription('Create a snapshot of the current BOM')
                    ->form([
                        TextInput::make('version_name')
                            ->label('Version Name')
                            ->placeholder('e.g., v2.0, Production, Prototype')
                            ->maxLength(255),

                        Textarea::make('change_notes')
                            ->label('Change Notes')
                            ->placeholder('Describe what changed in this version')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data) {
                        $product = $this->getOwnerRecord();

                        // Create version from current BOM
                        $version = BomVersion::createFromCurrentBom(
                            $product,
                            $data['change_notes'] ?? null,
                            auth()->id()
                        );

                        // Update version name if provided
                        if (!empty($data['version_name'])) {
                            $version->update(['version_name' => $data['version_name']]);
                        }

                        Notification::make()
                            ->title('BOM version created')
                            ->body("Version {$version->version_display} created successfully")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(fn ($record) => "BOM Version: {$record->version_display}")
                    ->modalContent(fn ($record) => view('filament.resources.products.bom-version-details', [
                        'version' => $record->load('bomVersionItems.componentProduct'),
                    ])),

                Action::make('activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activate BOM Version')
                    ->modalDescription(fn ($record) => "Are you sure you want to activate {$record->version_display}? This will archive the current active version.")
                    ->visible(fn ($record) => $record->status !== 'active')
                    ->action(function ($record) {
                        $record->activate();

                        Notification::make()
                            ->title('Version activated')
                            ->body("{$record->version_display} is now the active BOM version")
                            ->success()
                            ->send();
                    }),

                Action::make('compare')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.products.bom-comparison', [
                        'record' => $this->getOwnerRecord(),
                        'version1' => $record->id,
                    ])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_bom_versions')),
                ]),
            ])
            ->defaultSort('version_number', 'desc')
            ->emptyStateHeading('No BOM versions')
            ->emptyStateDescription('Create a version to track BOM changes over time')
            ->emptyStateIcon('heroicon-o-document-duplicate');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('version_name')
                    ->label('Version Name')
                    ->maxLength(255),

                Textarea::make('change_notes')
                    ->label('Change Notes')
                    ->rows(3)
                    ->maxLength(1000),
            ]);
    }
}
