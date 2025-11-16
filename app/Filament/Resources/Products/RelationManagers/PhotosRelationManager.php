<?php

namespace App\Filament\Resources\SupplierQuotes\Products\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use BackedEnum;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Photos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('file_type', 'photo'))
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
            ->actions([
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
            ->bulkActions([
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
                    ->directory('products/photos')
                    ->required()
                    ->maxSize(5120)
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        null,
                        '16:9',
                        '4:3',
                        '1:1',
                    ]),

                TextInput::make('original_filename')
                    ->label('Filename')
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(500),

                DatePicker::make('date_uploaded')
                    ->label('Upload Date')
                    ->default(now()),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
