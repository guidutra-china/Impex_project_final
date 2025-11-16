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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use BackedEnum;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Documents';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('file_type', 'document'))
            ->recordTitleAttribute('original_filename')
            ->columns([
                TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->original_filename)
                    ->icon('heroicon-o-document'),

                TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper(str_replace('application/', '', $state ?? 'file'))),

                TextColumn::make('description')
                    ->limit(50)
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
                    ->label('Upload Document')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['file_type'] = 'document';
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
            ->schema([
                Hidden::make('file_type')
                    ->default('document'),

                FileUpload::make('file_path')
                    ->label('Document')
                    ->disk('public')
                    ->directory('products/documents')
                    ->required()
                    ->maxSize(10240)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain',
                        'text/csv',
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
