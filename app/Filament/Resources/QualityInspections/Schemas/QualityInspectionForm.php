<?php

namespace App\Filament\Resources\QualityInspections\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class QualityInspectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('inspection_number')
                    ->required(),
                TextInput::make('inspectable_type')
                    ->required(),
                TextInput::make('inspectable_id')
                    ->required()
                    ->numeric(),
                Select::make('inspection_type')
                    ->options([
            'incoming' => 'Incoming',
            'in_process' => 'In process',
            'final' => 'Final',
            'random' => 'Random',
            'customer_return' => 'Customer return',
        ])
                    ->required(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ])
                    ->default('pending')
                    ->required(),
                Select::make('result')
                    ->options(['passed' => 'Passed', 'failed' => 'Failed', 'conditional' => 'Conditional']),
                DatePicker::make('inspection_date')
                    ->required(),
                DatePicker::make('completed_date'),
                Select::make('inspector_id')
                    ->relationship('inspector', 'name'),
                TextInput::make('inspector_name'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Textarea::make('failure_reason')
                    ->columnSpanFull(),
                Textarea::make('corrective_action')
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
