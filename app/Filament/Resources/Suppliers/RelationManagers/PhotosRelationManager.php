<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

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
use Illuminate\Support\Str;
use BackedEnum;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Photos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('document_type', ['other'])->where('mime_type', 'like', 'image/%'))
            ->recordTitleAttribute('title')
            ->columns([
                ImageColumn::make('file_path')
                    ->label(__('fields.file'))
                    ->disk('public')
                    ->size(120)
                    ->square(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->title),

                TextColumn::make('description')
                    ->limit(40)
                    ->placeholder('No description')
                    ->wrap(),

                TextColumn::make('issue_date')
                    ->label(__('fields.created_at'))
                    ->date()
                    ->sortable(),

                TextColumn::make('file_size_formatted')
                    ->label(__('fields.size'))
                    ->getStateUsing(fn ($record) => number_format($record->file_size / 1024, 2) . ' KB'),

                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Photo')
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $data['document_type'] = 'other';
                        $data['document_number'] = 'SUP-PHOTO-' . strtoupper(Str::random(8));
                        $data['related_type'] = 'App\\Models\\Supplier';
                        $data['related_id'] = $livewire->getOwnerRecord()->id;
                        $data['status'] = 'valid';
                        
                        if (isset($data['file_path']) && !isset($data['file_name'])) {
                            $data['file_name'] = basename($data['file_path']);
                            $data['safe_filename'] = Str::slug(pathinfo($data['file_name'], PATHINFO_FILENAME)) . '_' . Str::random(8);
                        }
                        
                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('common.download'))
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
            ->defaultSort('created_at', 'desc');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label(__('fields.file'))
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
                            $filename = $state->getClientOriginalName();
                            $set('file_name', $filename);
                            $set('title', pathinfo($filename, PATHINFO_FILENAME));
                            $set('file_size', $state->getSize());
                            $set('mime_type', $state->getMimeType());
                            $set('safe_filename', Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '_' . Str::random(8));
                        }
                    })
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('Photo Title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Photo Description')
                    ->maxLength(65535)
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('issue_date')
                    ->label(__('fields.created_at'))
                    ->required()
                    ->default(now())
                    ->native(false),

                Hidden::make('file_name'),
                Hidden::make('file_size'),
                Hidden::make('mime_type'),
                Hidden::make('safe_filename'),
            ])->columns(2);
    }
}
