<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Repositories\SupplierRepository;
use App\Repositories\DocumentRepository;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use BackedEnum;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Photos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected SupplierRepository $supplierRepository;
    protected DocumentRepository $documentRepository;

    public function mount(): void {
        parent::mount();
        $this->supplierRepository = app(SupplierRepository::class);
        $this->documentRepository = app(DocumentRepository::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->documentRepository->getSupplierPhotosQuery($this->getOwnerRecord()->id)
            )
            ->recordTitleAttribute('original_filename')
            ->columns([
                ImageColumn::make('file_path')
                    ->label('Photo')
                    ->disk('public')
                    ->size(120)
                    ->square(),

                TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->original_filename),

                TextColumn::make('description')
                    ->limit(40)
                    ->placeholder('No description')
                    ->wrap(),

                TextColumn::make('date_uploaded')
                    ->label('Upload Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('file_size_formatted')
                    ->label('Size'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Photo')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['file_type'] = 'photo';
                        if (isset($data['file_path']) && !isset($data['original_filename'])) {
                            $data['original_filename'] = basename($data['file_path']);
                        }
                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),

                EditAction::make(),

                DeleteAction::make()
                    ->after(function ($record) {
                        if ($record->file_path) {
                            Storage::disk('public')->delete($record->file_path);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                if ($record->file_path) {
                                    Storage::disk('public')->delete($record->file_path);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('file_type')
                    ->default('photo'),

                FileUpload::make('file_path')
                    ->label('Photo')
                    ->image()
                    ->disk('public')
                    ->directory('suppliers/photos')
                    ->required()
                    ->maxSize(5120)
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        null,
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->preserveFilenames()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state && is_object($state) && method_exists($state, 'getClientOriginalName')) {
                            $set('original_filename', $state->getClientOriginalName());
                            $set('file_size', $state->getSize());
                            $set('mime_type', $state->getMimeType());
                        }
                    })
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Photo Description')
                    ->maxLength(65535)
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('date_uploaded')
                    ->label('Upload Date')
                    ->required()
                    ->default(now())
                    ->native(false),

                TextInput::make('sort_order')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first'),

                Hidden::make('original_filename'),
                Hidden::make('file_size'),
                Hidden::make('mime_type'),
            ])->columns(2);
    }
}
