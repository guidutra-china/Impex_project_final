<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Jobs\AnalyzeImportFileJob;
use App\Jobs\ProcessProductImportJob;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewDocumentImport extends ViewRecord
{
    protected static string $resource = DocumentImportResource::class;

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            // Re-analyze action (visible when failed or ready)
            Action::make('reanalyze')
                ->label('Re-analyze File')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, ['failed', 'ready']))
                ->requiresConfirmation()
                ->modalHeading('Re-analyze File')
                ->modalDescription('This will re-analyze the file with AI. Any previous analysis will be replaced.')
                ->action(function () {
                    $this->record->update([
                        'status' => 'pending',
                        'ai_analysis' => null,
                        'column_mapping' => null,
                        'analyzed_at' => null,
                    ]);
                    
                    AnalyzeImportFileJob::dispatch($this->record);
                    
                    Notification::make()
                        ->title('Re-analysis Started')
                        ->body('The file is being re-analyzed. Please wait...')
                        ->success()
                        ->send();
                }),

            // Start import action (visible when ready)
            Action::make('startImport')
                ->label('Start Import')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->record->status === 'ready')
                ->requiresConfirmation()
                ->modalHeading('Start Product Import')
                ->modalDescription(function () {
                    $count = $this->record->total_rows ?? 0;
                    return "This will import {$count} products based on the AI analysis. This process may take several minutes.";
                })
                ->action(function () {
                    ProcessProductImportJob::dispatch($this->record);
                    
                    Notification::make()
                        ->title('Import Started')
                        ->body('Products are being imported. You will be notified when it\'s complete.')
                        ->success()
                        ->send();
                }),

            // View AI Analysis action (visible when analyzed)
            Action::make('viewAnalysis')
                ->label('View AI Analysis')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn () => !empty($this->record->ai_analysis))
                ->modalHeading('AI Analysis Results')
                ->modalContent(function () {
                    $analysis = $this->record->ai_analysis;
                    return view('filament.modals.ai-analysis', [
                        'analysis' => $analysis,
                        'mapping' => $this->record->column_mapping,
                    ]);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Import Information')
                    ->schema([
                        TextEntry::make('file_name')
                            ->label('File Name'),
                        
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($record) => $record->status_color),
                        
                        TextEntry::make('import_type')
                            ->label('Import Type')
                            ->badge(),
                        
                        TextEntry::make('document_type')
                            ->label('Document Type')
                            ->default('N/A'),
                        
                        TextEntry::make('formatted_file_size')
                            ->label('File Size'),
                        
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        
                        TextEntry::make('analyzed_at')
                            ->label('Analyzed At')
                            ->dateTime()
                            ->default('Not analyzed yet')
                            ->visible(fn ($record) => !empty($record->analyzed_at)),
                        
                        TextEntry::make('imported_at')
                            ->label('Imported At')
                            ->dateTime()
                            ->default('Not imported yet')
                            ->visible(fn ($record) => !empty($record->imported_at)),
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
                            ->label('Total Rows')
                            ->badge(),
                        
                        TextEntry::make('success_count')
                            ->label('Success')
                            ->badge()
                            ->color('success'),
                        
                        TextEntry::make('updated_count')
                            ->label('Updated')
                            ->badge()
                            ->color('info'),
                        
                        TextEntry::make('skipped_count')
                            ->label('Skipped')
                            ->badge()
                            ->color('warning'),
                        
                        TextEntry::make('error_count')
                            ->label('Errors')
                            ->badge()
                            ->color('danger'),
                        
                        TextEntry::make('success_rate')
                            ->label('Success Rate')
                            ->suffix('%')
                            ->badge()
                            ->color('success'),
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
