<?php

namespace App\Filament\Resources\DocumentImports\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentImportTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->limit(30),

                BadgeColumn::make('import_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'products',
                        'success' => 'suppliers',
                        'warning' => 'clients',
                        'info' => 'quotes',
                    ]),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'info' => fn ($state) => in_array($state, ['analyzing', 'importing']),
                        'warning' => 'ready',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),

                TextColumn::make('document_type')
                    ->label('Document Type')
                    ->default('N/A'),

                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->default('N/A')
                    ->limit(20),

                TextColumn::make('total_rows')
                    ->label('Total')
                    ->alignCenter(),

                TextColumn::make('success_count')
                    ->label('Success')
                    ->alignCenter()
                    ->color('success'),

                TextColumn::make('error_count')
                    ->label('Errors')
                    ->alignCenter()
                    ->color('danger'),

                TextColumn::make('user.name')
                    ->label('Imported By')
                    ->default('Unknown'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Add filters here if needed
            ]);
    }
}
