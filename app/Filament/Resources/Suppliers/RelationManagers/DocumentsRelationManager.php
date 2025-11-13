<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Documents';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;


    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('file_type', 'document'))
            ->recordTitleAttribute('original_filename')
            ->columns([
                TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable()
                    ->icon('heroicon-o-document')
                    ->iconColor('primary')
                    ->weight('medium'),
                
                TextColumn::make('description')
                    ->limit(60)
                    ->placeholder('No description')
                    ->wrap(),
                
                TextColumn::make('date_uploaded')
                    ->label('Upload Date')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('file_size_formatted')
                    ->label('Size'),
//
                TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'application/pdf' => 'PDF',
                        'application/msword' => 'Word',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
                        'application/vnd.ms-excel' => 'Excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
                        default => 'Document',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'application/pdf' => 'danger',
                        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'info',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'success',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Documents')
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
            ->defaultSort('date_uploaded', 'desc');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('file_type')
                    ->default('document'),
                
                FileUpload::make('file_path')
                    ->label('Document')
                    ->disk('public')
                    ->directory('suppliers/documents')
                    ->required()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240)
                    ->preserveFilenames()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state && is_object($state) && method_exists($state, 'getClientOriginalName')) {
                            $set('original_filename', $state->getClientOriginalName());
                            $set('file_size', $state->getSize());
                            $set('mime_type', $state->getMimeType());
                        }
                    })
                    ->helperText('Accepted formats: PDF, Word (.doc, .docx), Excel (.xls, .xlsx) - Max 10MB')
                    ->columnSpanFull(),
                
                Textarea::make('description')
                    ->label('Document Description')
                    ->maxLength(65535)
                    ->rows(3)
                    ->columnSpanFull(),
                
                DatePicker::make('date_uploaded')
                    ->label('Upload Date')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->columnSpanFull(),
                
                Hidden::make('original_filename'),
                Hidden::make('file_size'),
                Hidden::make('mime_type'),
            ]);
    }
}
