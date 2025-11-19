<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Enums\CountryTypeEnum;
use App\Models\Client;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('code')
                            ->label('Client Code (5 letters)')
                            ->required()
                            ->length(5)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[A-Z]{5}$/')
                            ->validationMessages([
                                'regex' => 'Code must be exactly 5 uppercase letters.',
                                'unique' => 'This code is already in use by another client.',
                            ])
                            ->helperText('Unique 5-letter code for RFQ numbering (e.g., AMAZN for Amazon)')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Auto-uppercase
                                if ($state) {
                                    $set('code', strtoupper($state));
                                }
                            })
                            ->placeholder('AMAZN'),

                        TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                // Auto-suggest code if not set
                                if ($state && !$get('code')) {
                                    $suggested = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $state), 0, 5));
                                    if (strlen($suggested) === 5) {
                                        $set('code', $suggested);
                                    }
                                }
                            }),

                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('address')
                            ->label('Address')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label('City')
                            ->maxLength(255),

                        TextInput::make('state')
                            ->label('State/Province')
                            ->maxLength(255),

                        TextInput::make('zip')
                            ->label('ZIP/Postal Code')
                            ->maxLength(255),

                        Select::make('country')
                            ->options(CountryTypeEnum::toArray())
                            ->searchable(),

                        TextInput::make('tax_number')
                            ->label('Tax Number')
                            ->placeholder('00.000.000/0000-00')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpan(['lg' => fn (?Client $record) => $record === null ? 3 : 2]),

                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->state(fn (Client $record): ?string => $record->created_at?->diffForHumans()),

                        TextEntry::make('updated_at')
                            ->label('Last modified at')
                            ->state(fn (Client $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Client $record) => $record === null),
            ])
            ->columns(3);
    }
}
