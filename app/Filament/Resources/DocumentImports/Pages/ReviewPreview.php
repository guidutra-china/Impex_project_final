<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Jobs\ImportSelectedItemsJob;
use App\Models\ImportPreviewItem;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ReviewPreview extends Page implements HasTable
{
    use InteractsWithTable;
    use \Filament\Resources\Pages\Concerns\InteractsWithRecord;

    protected static string $resource = DocumentImportResource::class;
    
    protected string $view = 'filament.resources.document-imports.pages.review-preview';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ImportPreviewItem::query()
                    ->where('import_history_id', $this->record->id)
                    ->with(['existingProduct'])
            )
            ->columns([
                Tables\Columns\CheckboxColumn::make('selected')
                    ->label('Import')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('row_number')
                    ->label('Row')
                    ->sortable()
                    ->width('60px'),
                
                Tables\Columns\TextColumn::make('duplicate_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => match($record->duplicate_status) {
                        'duplicate' => 'danger',
                        'similar' => 'warning',
                        'new' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($record) => match($record->duplicate_status) {
                        'duplicate' => 'DUPLICATE',
                        'similar' => 'SIMILAR',
                        'new' => 'NEW',
                        default => 'UNKNOWN',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('data.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('data.name')
                    ->label('Product Name')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('data.price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('photo_status')
                    ->label('Photo')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'extracted' => 'success',
                        'uploaded' => 'success',
                        'missing' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('validation_errors')
                    ->label('Errors')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => $state ? count(json_decode($state, true)) : 0)
                    ->visible(fn ($record) => !empty($record->validation_errors)),
                
                Tables\Columns\SelectColumn::make('action')
                    ->label('Action')
                    ->options([
                        'import' => 'Import',
                        'skip' => 'Skip',
                        'update' => 'Update Existing',
                        'merge' => 'Merge Data',
                    ])
                    ->selectablePlaceholder(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('duplicate_status')
                    ->label('Status')
                    ->options([
                        'new' => 'New',
                        'duplicate' => 'Duplicate',
                        'similar' => 'Similar',
                    ])
                    ->placeholder('All'),
                
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'import' => 'Import',
                        'skip' => 'Skip',
                        'update' => 'Update',
                        'merge' => 'Merge',
                    ])
                    ->placeholder('All'),
                
                Tables\Filters\TernaryFilter::make('selected')
                    ->label('Selected')
                    ->placeholder('All')
                    ->trueLabel('Selected')
                    ->falseLabel('Not Selected'),
                
                Tables\Filters\TernaryFilter::make('has_errors')
                    ->label('Has Errors')
                    ->placeholder('All')
                    ->trueLabel('With Errors')
                    ->falseLabel('Valid')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('validation_errors'),
                        false: fn ($query) => $query->whereNull('validation_errors'),
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('selectAll')
                    ->label('Select All')
                    ->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each->update(['selected' => true]))
                    ->deselectRecordsAfterCompletion(),
                
                    Action::make('deselectAll')
                    ->label('Deselect All')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn ($records) => $records->each->update(['selected' => false]))
                    ->deselectRecordsAfterCompletion(),
                
                    Action::make('skipDuplicates')
                    ->label('Skip Duplicates')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('warning')
                    ->action(fn ($records) => 
                        $records->where('duplicate_status', 'duplicate')
                            ->each->update(['selected' => false, 'action' => 'skip'])
                    )
                    ->deselectRecordsAfterCompletion(),
                
                    Action::make('setActionImport')
                    ->label('Set Action: Import')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn ($records) => $records->each->update(['action' => 'import']))
                    ->deselectRecordsAfterCompletion(),
                
                    Action::make('setActionSkip')
                    ->label('Set Action: Skip')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn ($records) => $records->each->update(['action' => 'skip', 'selected' => false]))
                    ->deselectRecordsAfterCompletion(),
                
                    DeleteBulkAction::make()
                        ->label('Remove Selected'),
                ]),
            ])
            ->defaultSort('row_number')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        $stats = $this->getStatistics();
        
        return [
            Action::make('viewStatistics')
                ->label('Statistics')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->modalHeading('Import Statistics')
                ->modalContent(view('filament.modals.import-statistics', ['stats' => $stats]))
                ->modalWidth('2xl')
                ->slideOver(),
            
            Action::make('importSelected')
                ->label('Import Selected Items')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm Import')
                ->modalDescription(fn () => 
                    "You are about to import {$stats['selected']} selected items. " .
                    "This action cannot be undone."
                )
                ->action(function () {
                    $selectedCount = ImportPreviewItem::query()
                        ->where('import_history_id', $this->record->id)
                        ->where('selected', true)
                        ->count();
                    
                    if ($selectedCount === 0) {
                        Notification::make()
                            ->title('No Items Selected')
                            ->body('Please select at least one item to import.')
                            ->warning()
                            ->send();
                        
                        return;
                    }
                    
                    // Update status
                    $this->record->update(['status' => 'importing']);
                    
                    // Dispatch import job
                    ImportSelectedItemsJob::dispatch($this->record);
                    
                    Notification::make()
                        ->title('Import Started')
                        ->body("Importing {$selectedCount} items in the background.")
                        ->success()
                        ->send();
                    
                    return redirect()->route('filament.admin.resources.document-imports.view', ['record' => $this->record->id]);
                }),
            
            Action::make('backToView')
                ->label('Back to Import')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => DocumentImportResource::getUrl('view', ['record' => $this->record->id])),
        ];
    }

    protected function getStatistics(): array
    {
        $query = ImportPreviewItem::query()->where('import_history_id', $this->record->id);
        
        return [
            'total' => $query->count(),
            'selected' => $query->where('selected', true)->count(),
            'new' => $query->where('duplicate_status', 'new')->count(),
            'duplicates' => $query->where('duplicate_status', 'duplicate')->count(),
            'similar' => $query->where('duplicate_status', 'similar')->count(),
            'errors' => $query->whereNotNull('validation_errors')->count(),
            'photos_extracted' => $query->where('photo_status', 'extracted')->count(),
            'photos_missing' => $query->where('photo_status', 'missing')->count(),
        ];
    }
}
