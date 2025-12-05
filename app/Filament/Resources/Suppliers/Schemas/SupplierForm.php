<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Enums\CountryTypeEnum;
use App\Models\Supplier;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;


class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('supplier_code')
                            ->label('Supplier Code')
                            ->length(5)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[A-Z]{5}$/')
                            ->helperText('Auto-generated from company name. Leave empty to auto-generate.')
                            ->placeholder('Auto-generated')
                            ->maxLength(5)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Auto-uppercase
                                if ($state) {
                                    $set('supplier_code', strtoupper($state));
                                }
                            }),

                        TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),

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
                    ])
                    ->columns(2)
                    ->columnSpan(['lg' => fn (?Supplier $record) => $record === null ? 3 : 2]),

                Section::make()
                    ->schema([
                        Select::make('tags')
                            ->label('Tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Tag Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state)))
                                    ->helperText('Tag name must be unique'),
                                
                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique()
                                    ->helperText('URL-friendly identifier (auto-generated from name)'),
                            ])
                            ->createOptionModalHeading('Create New Tag')
                            ->createOptionUsing(function (array $data) {
                                $tag = \App\Models\Tag::create($data);
                                return $tag->id;
                            }),

                        TextEntry::make('created_at')
                            ->state(fn (Supplier $record): ?string => $record->created_at?->diffForHumans()),

                        TextEntry::make('updated_at')
                            ->label('Last modified at')
                            ->state(fn (Supplier $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Supplier $record) => $record === null),
            ])
            ->columns(3);
    }
}