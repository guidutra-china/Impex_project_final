<?php

namespace App\Filament\Resources\SupplierQuotes\Pages;

use App\Filament\Resources\SupplierQuotes\SupplierQuoteResource;
use App\Services\SupplierQuoteImportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSupplierQuote extends EditRecord
{
    protected static string $resource = SupplierQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_excel')
                ->label('Import from Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    FileUpload::make('excel_file')
                        ->label('Excel File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required()
                        ->maxSize(5120) // 5MB
                        ->helperText('Upload the RFQ Excel file filled by the supplier with prices.')
                        ->disk('local')
                        ->directory('temp/imports'),
                ])
                ->action(function (array $data, SupplierQuoteImportService $importService) {
                    $filePath = storage_path('app/' . $data['excel_file']);
                    
                    try {
                        $result = $importService->importFromExcel($this->record, $filePath);
                    } catch (\App\Exceptions\RFQImportException $e) {
                        Notification::make()
                            ->danger()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->send();
                        return;
                    }
                    
                    // Clean up uploaded file
                    try {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete temporary import file', [
                            'file' => basename($filePath),
                            'error' => $e->getMessage(),
                        ]);
                    }
                    
                    if ($result['success']) {
                        Notification::make()
                            ->success()
                            ->title('Import Successful')
                            ->body($result['message'])
                            ->send();
                        
                        // Show errors if any
                        if (!empty($result['errors'])) {
                            Notification::make()
                                ->warning()
                                ->title('Import Warnings')
                                ->body(implode("\n", array_slice($result['errors'], 0, 5)))
                                ->send();
                        }
                        
                        // Refresh the page to show new items
                        redirect()->to(static::getResource()::getUrl('edit', ['record' => $this->record]));
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Import Failed')
                            ->body($result['message'])
                            ->send();
                    }
                }),
            DeleteAction::make(),
            Action::make('recalculate')
                ->label('Recalculate All')
                ->icon('heroicon-o-calculator')
                ->action(function () {
                    $this->record->lockExchangeRate();
                    $this->record->calculateCommission();
                })
                ->requiresConfirmation()
                ->color('warning'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\RelatedDocumentsWidget::class,
        ];
    }
}
