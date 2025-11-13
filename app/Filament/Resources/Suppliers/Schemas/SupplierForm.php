<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Supplier Information')
                    ->tabs([
                        Tabs\Tab::make('General Data')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('address')
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('city')
                                    ->maxLength(255),
                                TextInput::make('state')
                                    ->maxLength(255),
                                TextInput::make('zip')
                                    ->maxLength(20),
                                TextInput::make('country')
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                                TextInput::make('website')
                                    ->url()
                                    ->maxLength(255),
                                Select::make('tags')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->columnSpan(2),
                            ])->columns(2),
                        
                        Tabs\Tab::make('Photos')
                            ->schema([
                                FileUpload::make('photos')
                                    ->image()
                                    ->multiple()
                                    ->disk('public')
                                    ->directory('suppliers/photos')
                                    ->visibility('public')  // Add this
                                    ->maxSize(5120)
                                    ->maxFiles(10)
                                    ->reorderable()
                                    ->appendFiles()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->panelLayout('grid')
                                    ->helperText('Upload supplier photos (max 10 files, 5MB each)')
                                    ->columnSpanFull(),
                            ]),
                        
                        Tabs\Tab::make('Documents')
                            ->schema([
                                FileUpload::make('documents')
                                    ->multiple()
                                    ->disk('public')
                                    ->directory('suppliers/documents')
                                    ->visibility('public')  // Add this
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->maxSize(10240)
                                    ->maxFiles(20)
                                    ->reorderable()
                                    ->appendFiles()
                                    ->downloadable()
                                    ->openable()
                                    ->previewable(false)
                                    ->helperText('Upload documents (PDF, Word, Excel - max 20 files, 10MB each)')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
