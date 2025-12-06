<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\CommercialInvoice;
use App\Services\CommercialInvoicePdfService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf_original')
                ->label('PDF Original')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->visible(fn () => in_array($this->record->status, ['on_board', 'in_transit', 'customs_clearance', 'delivered']))
                ->action(function () {
                    try {
                        $pdfService = app(CommercialInvoicePdfService::class);
                        $path = $pdfService->generate($this->record, 'original');
                        
                        Notification::make()
                            ->success()
                            ->title('PDF Generated')
                            ->body('Commercial Invoice PDF (Original) generated successfully')
                            ->send();
                        
                        return response()->download(storage_path('app/' . $path));
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error Generating PDF')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            
            Action::make('pdf_customs')
                ->label('PDF Customs')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, ['on_board', 'in_transit', 'customs_clearance', 'delivered']) && $this->record->commercialInvoice?->customs_discount_percentage > 0)
                ->action(function () {
                    try {
                        $pdfService = app(CommercialInvoicePdfService::class);
                        $path = $pdfService->generate($this->record, 'customs');
                        
                        Notification::make()
                            ->success()
                            ->title('PDF Generated')
                            ->body('Commercial Invoice PDF (Customs) generated successfully')
                            ->send();
                        
                        return response()->download(storage_path('app/' . $path));
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error Generating PDF')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
