<?php

namespace App\Filament\Resources\ImportHistories\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ImportHistoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('file_name')
                    ->required(),
                TextInput::make('file_type')
                    ->required(),
                TextInput::make('file_path'),
                TextInput::make('file_size')
                    ->numeric(),
                TextInput::make('import_type')
                    ->required(),
                TextInput::make('document_type'),
                TextInput::make('ai_analysis'),
                TextInput::make('column_mapping'),
                TextInput::make('supplier_name'),
                TextInput::make('supplier_email')
                    ->email(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'analyzing' => 'Analyzing',
            'ready' => 'Ready',
            'importing' => 'Importing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ])
                    ->default('pending')
                    ->required(),
                TextInput::make('total_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('success_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('updated_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('skipped_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('error_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('warning_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('errors'),
                TextInput::make('warnings'),
                Textarea::make('result_message')
                    ->columnSpanFull(),
                DateTimePicker::make('analyzed_at'),
                DateTimePicker::make('imported_at'),
            ]);
    }
}
