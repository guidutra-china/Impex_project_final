<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\RFQExcelService;
use App\Services\RFQImportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

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
                        ->helperText('Upload an Excel file (.xlsx) with products and prices to import into this RFQ.')
                        ->disk('local')
                        ->directory('temp/imports'),
                ])
                ->action(function (array $data, RFQImportService $importService) {
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
                    if (file_exists($filePath)) {
                        unlink($filePath);
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
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            Action::make('download_rfq_excel')
                ->label('Download RFQ Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $order = $this->record;
                    
                    // Generate Excel
                    $excelService = new RFQExcelService();
                    $filePath = $excelService->generateRFQ($order);
                    
                    // Stream the file
                    return response()->download($filePath, basename($filePath))->deleteFileAfterSend(true);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }
}
