<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Jobs\ImportSelectedItemsJob;
use App\Models\ImportPreviewItem;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms;

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
                    ->color(fn ($record) => $record->duplicate_status_color)
                    ->formatStateUsing(fn ($record) => $record->duplicate_status_label)
                    ->sortable(),
                
                Tables\Columns\ImageColumn::make('photo_temp_path')
                    ->label('Photo')
                    ->disk('public')
                    ->defaultImageUrl('/images/no-image.png')
                    ->circular()
                    ->width('50px')
                    ->height('50px'),
                
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->name),
                
                Tables\Columns\TextColumn::make('formatted_price')
                    ->label('Price')
                    ->sortable('price'),
                
                Tables\Columns\TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('moq')
                    ->label('MOQ')
                    ->sortable()
                    ->placeholder('N/A')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('photo_status')
                    ->label('Photo Status')
                    ->badge()
                    ->color(fn ($record) => $record->photo_status_color)
                    ->formatStateUsing(fn ($record) => $record->photo_status_label)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn ($record) => $record->action_color)
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('has_errors')
                    ->label('Valid')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('duplicate_status')
                    ->label('Status')
                    ->options([
                        'new' => 'New',
                        'duplicate' => 'Duplicate',
                        'similar' => 'Similar',
                    ]),
                
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'import' => 'Import',
                        'skip' => 'Skip',
                        'update' => 'Update',
                        'merge' => 'Merge',
                    ]),
                
                Tables\Filters\SelectFilter::make('photo_status')
                    ->label('Photo Status')
                    ->options([
                        'extracted' => 'Extracted',
                        'uploaded' => 'Uploaded',
                        'missing' => 'Missing',
                        'error' => 'Error',
                        'none' => 'No Photo',
                    ]),
                
                Tables\Filters\TernaryFilter::make('selected')
                    ->label('Selected for Import')
                    ->placeholder('All')
                    ->trueLabel('Selected')
                    ->falseLabel('Not Selected'),
                
                Tables\Filters\TernaryFilter::make('has_errors')
                    ->label('Has Errors')
                    ->placeholder('All')
                    ->trueLabel('With Errors')
                    ->falseLabel('Valid'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Preview Item Details')
                    ->modalWidth('7xl')
                    ->form([
                        Forms\Components\Tabs::make('Details')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Basic Info')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('sku')
                                                    ->label('SKU'),
                                                Forms\Components\TextInput::make('supplier_code')
                                                    ->label('Supplier Code'),
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Product Name')
                                                    ->required()
                                                    ->columnSpanFull(),
                                                Forms\Components\Textarea::make('description')
                                                    ->label('Description')
                                                    ->rows(3)
                                                    ->columnSpanFull(),
                                                Forms\Components\TextInput::make('brand')
                                                    ->label('Brand'),
                                                Forms\Components\TextInput::make('model_number')
                                                    ->label('Model Number'),
                                            ]),
                                    ]),
                                
                                Forms\Components\Tabs\Tab::make('Pricing')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('price')
                                                    ->label('Price (cents)')
                                                    ->numeric(),
                                                Forms\Components\TextInput::make('cost')
                                                    ->label('Cost (cents)')
                                                    ->numeric(),
                                                Forms\Components\TextInput::make('msrp')
                                                    ->label('MSRP (cents)')
                                                    ->numeric(),
                                            ]),
                                    ]),
                                
                                Forms\Components\Tabs\Tab::make('Duplicate Check')
                                    ->schema([
                                        Forms\Components\Placeholder::make('duplicate_info')
                                            ->label('')
                                            ->content(function ($record) {
                                                if ($record->isNew()) {
                                                    return '✅ This is a new product';
                                                }
                                                
                                                if ($record->isDuplicate() || $record->isSimilar()) {
                                                    $existing = $record->existingProduct;
                                                    if (!$existing) {
                                                        return '⚠️ Marked as duplicate but existing product not found';
                                                    }
                                                    
                                                    return view('filament.components.duplicate-comparison', [
                                                        'preview' => $record,
                                                        'existing' => $existing,
                                                    ]);
                                                }
                                                
                                                return 'Unknown status';
                                            }),
                                        
                                        Forms\Components\Select::make('action')
                                            ->label('Action to Take')
                                            ->options([
                                                'import' => 'Import as New',
                                                'skip' => 'Skip (Don\'t Import)',
                                                'update' => 'Update Existing',
                                                'merge' => 'Merge Data',
                                            ])
                                            ->required(),
                                    ]),
                                
                                Forms\Components\Tabs\Tab::make('Validation')
                                    ->schema([
                                        Forms\Components\Placeholder::make('validation_errors')
                                            ->label('Errors')
                                            ->content(fn ($record) => 
                                                !empty($record->validation_errors) 
                                                    ? implode("\n", $record->validation_errors)
                                                    : '✅ No errors'
                                            ),
                                        
                                        Forms\Components\Placeholder::make('validation_warnings')
                                            ->label('Warnings')
                                            ->content(fn ($record) => 
                                                !empty($record->validation_warnings)
                                                    ? implode("\n", $record->validation_warnings)
                                                    : '✅ No warnings'
                                            ),
                                    ]),
                            ]),
                    ]),
                
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Preview Item')
                    ->modalWidth('5xl')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU'),
                                Forms\Components\TextInput::make('supplier_code')
                                    ->label('Supplier Code'),
                                Forms\Components\TextInput::make('name')
                                    ->label('Product Name')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('price')
                                    ->label('Price (cents)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('brand')
                                    ->label('Brand'),
                                Forms\Components\FileUpload::make('photo_path')
                                    ->label('Product Photo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products/import-manual')
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('action')
                                    ->label('Action')
                                    ->options([
                                        'import' => 'Import',
                                        'skip' => 'Skip',
                                        'update' => 'Update',
                                        'merge' => 'Merge',
                                    ])
                                    ->required(),
                                Forms\Components\Checkbox::make('selected')
                                    ->label('Selected for Import'),
                            ]),
                    ]),
                
                Tables\Actions\DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove from Preview'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('selectAll')
                    ->label('Select All')
                    ->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each->update(['selected' => true]))
                    ->deselectRecordsAfterCompletion(),
                
                Tables\Actions\BulkAction::make('deselectAll')
                    ->label('Deselect All')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn ($records) => $records->each->update(['selected' => false]))
                    ->deselectRecordsAfterCompletion(),
                
                Tables\Actions\BulkAction::make('skipDuplicates')
                    ->label('Skip Duplicates')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('warning')
                    ->action(fn ($records) => 
                        $records->where('duplicate_status', 'duplicate')
                            ->each->update(['selected' => false, 'action' => 'skip'])
                    )
                    ->deselectRecordsAfterCompletion(),
                
                Tables\Actions\BulkAction::make('setActionImport')
                    ->label('Set Action: Import')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn ($records) => $records->each->update(['action' => 'import']))
                    ->deselectRecordsAfterCompletion(),
                
                Tables\Actions\BulkAction::make('setActionSkip')
                    ->label('Set Action: Skip')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn ($records) => $records->each->update(['action' => 'skip', 'selected' => false]))
                    ->deselectRecordsAfterCompletion(),
                
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Remove Selected'),
            ])
            ->defaultSort('row_number')
            ->striped()
            ->paginated([10, 25, 50, 100, 'all']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importSelected')
                ->label('Import Selected Items')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Import Selected Products')
                ->modalDescription(function () {
                    $selectedCount = $this->record->previewItems()->where('selected', true)->count();
                    $totalCount = $this->record->previewItems()->count();
                    return "You are about to import {$selectedCount} out of {$totalCount} products. This action cannot be undone.";
                })
                ->action(function () {
                    $selectedCount = $this->record->previewItems()->where('selected', true)->count();
                    
                    if ($selectedCount === 0) {
                        Notification::make()
                            ->title('No Items Selected')
                            ->body('Please select at least one item to import.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    // Dispatch import job
                    ImportSelectedItemsJob::dispatch($this->record);
                    
                    Notification::make()
                        ->title('Import Started')
                        ->body("{$selectedCount} products are being imported. This may take a few minutes...")
                        ->success()
                        ->send();
                    
                    // Redirect to view page
                    return redirect()->to(
                        DocumentImportResource::getUrl('view', ['record' => $this->record])
                    );
                }),
            
            Action::make('statistics')
                ->label('Statistics')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalHeading('Import Preview Statistics')
                ->modalContent(function () {
                    $stats = [
                        'total' => $this->record->previewItems()->count(),
                        'selected' => $this->record->previewItems()->where('selected', true)->count(),
                        'new' => $this->record->previewItems()->where('duplicate_status', 'new')->count(),
                        'duplicate' => $this->record->previewItems()->where('duplicate_status', 'duplicate')->count(),
                        'similar' => $this->record->previewItems()->where('duplicate_status', 'similar')->count(),
                        'with_errors' => $this->record->previewItems()->where('has_errors', true)->count(),
                        'with_photos' => $this->record->previewItems()->where('photo_status', 'extracted')->count(),
                        'missing_photos' => $this->record->previewItems()->where('photo_status', 'missing')->count(),
                    ];
                    
                    return view('filament.modals.import-statistics', ['stats' => $stats]);
                }),
        ];
    }
}
