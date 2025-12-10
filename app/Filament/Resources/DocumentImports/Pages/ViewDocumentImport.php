<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;

class ViewDocumentImport extends ViewRecord
{
    protected static string $resource = DocumentImportResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Import Information')
                    ->schema([
                        TextEntry::make('file_name')
                            ->label('File Name'),
                        
                        BadgeEntry::make('status')
                            ->label('Status'),
                        
                        TextEntry::make('import_type')
                            ->label('Import Type'),
                        
                        TextEntry::make('document_type')
                            ->label('Document Type')
                            ->default('N/A'),
                        
                        TextEntry::make('formatted_file_size')
                            ->label('File Size'),
                        
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Supplier Information')
                    ->schema([
                        TextEntry::make('supplier_name')
                            ->label('Supplier Name')
                            ->default('N/A'),
                        
                        TextEntry::make('supplier_email')
                            ->label('Supplier Email')
                            ->default('N/A'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => !empty($record->supplier_name)),

                Section::make('Import Results')
                    ->schema([
                        TextEntry::make('total_rows')
                            ->label('Total Rows'),
                        
                        TextEntry::make('success_count')
                            ->label('Success')
                            ->color('success'),
                        
                        TextEntry::make('updated_count')
                            ->label('Updated')
                            ->color('info'),
                        
                        TextEntry::make('skipped_count')
                            ->label('Skipped')
                            ->color('warning'),
                        
                        TextEntry::make('error_count')
                            ->label('Errors')
                            ->color('danger'),
                        
                        TextEntry::make('success_rate')
                            ->label('Success Rate')
                            ->suffix('%'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->isCompleted()),

                Section::make('Result Message')
                    ->schema([
                        TextEntry::make('result_message')
                            ->label('')
                            ->default('No message'),
                    ])
                    ->visible(fn ($record) => !empty($record->result_message)),

                Section::make('Errors')
                    ->schema([
                        TextEntry::make('errors')
                            ->label('')
                            ->listWithLineBreaks()
                            ->default(['No errors']),
                    ])
                    ->visible(fn ($record) => !empty($record->errors)),

                Section::make('Warnings')
                    ->schema([
                        TextEntry::make('warnings')
                            ->label('')
                            ->listWithLineBreaks()
                            ->default(['No warnings']),
                    ])
                    ->visible(fn ($record) => !empty($record->warnings)),
            ]);
    }
}
