<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\RFQExcelService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert items requested_unit_price from cents to decimal
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                if (isset($item['requested_unit_price'])) {
                    $data['items'][$key]['requested_unit_price'] = $item['requested_unit_price'] / 100;
                }
            }
        }

        return $data;
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
