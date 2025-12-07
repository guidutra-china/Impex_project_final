<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\CommercialInvoice;
use App\Services\CommercialInvoicePdfService;
use App\Services\PackingListPdfService;
use App\Services\PackingListExcelService;
use App\Models\PackingList;
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

    /**
     * Handle saving of commercialInvoice relationship data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract commercialInvoice data before saving Shipment
        if (isset($data['commercialInvoice'])) {
            $this->commercialInvoiceData = $data['commercialInvoice'];
            
            // DEBUG: Log what we're about to save
            \Log::info('EditShipment: commercialInvoice data extracted', [
                'shipment_id' => $this->record->id,
                'data' => $this->commercialInvoiceData,
                'display_options' => $this->commercialInvoiceData['display_options'] ?? 'NOT SET',
            ]);
            
            unset($data['commercialInvoice']);
        } else {
            \Log::warning('EditShipment: No commercialInvoice data in form', [
                'shipment_id' => $this->record->id,
                'form_keys' => array_keys($data),
            ]);
        }
        
        return $data;
    }

    /**
     * Save commercialInvoice after Shipment is saved
     */
    protected function afterSave(): void
    {
        if (isset($this->commercialInvoiceData)) {
            // Get or create CommercialInvoice
            $commercialInvoice = $this->record->commercialInvoice ?? new CommercialInvoice();
            $isNew = !$commercialInvoice->exists;
            $commercialInvoice->shipment_id = $this->record->id;
            
            // Fill data
            $commercialInvoice->fill($this->commercialInvoiceData);
            
            // Ensure invoice_number is generated if not exists
            if (!$commercialInvoice->invoice_number) {
                $commercialInvoice->invoice_number = CommercialInvoice::generateInvoiceNumber();
            }
            
            $commercialInvoice->save();
            
            // DEBUG: Log what was saved
            \Log::info('EditShipment: commercialInvoice saved', [
                'action' => $isNew ? 'created' : 'updated',
                'commercial_invoice_id' => $commercialInvoice->id,
                'shipment_id' => $this->record->id,
                'customs_discount' => $commercialInvoice->customs_discount_percentage,
                'display_options' => $commercialInvoice->display_options,
            ]);
        } else {
            \Log::warning('EditShipment: No commercialInvoiceData to save', [
                'shipment_id' => $this->record->id,
            ]);
        }
    }

    protected array $commercialInvoiceData = [];

    /**
     * Load commercialInvoice data into form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load commercialInvoice data if exists
        if ($this->record->commercialInvoice) {
            $data['commercialInvoice'] = $this->record->commercialInvoice->toArray();
        }
        
        return $data;
    }

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
            
            Action::make('packing_list_pdf')
                ->label('Packing List PDF')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['on_board', 'in_transit', 'customs_clearance', 'delivered']))
                ->action(function () {
                    try {
                        // Create or get PackingList
                        $packingList = $this->record->packingList;
                        if (!$packingList) {
                            $packingList = PackingList::generateFromShipment($this->record);
                        }
                        
                        $pdfService = app(PackingListPdfService::class);
                        $path = $pdfService->generate($this->record);
                        
                        Notification::make()
                            ->success()
                            ->title('PDF Generated')
                            ->body('Packing List PDF generated successfully')
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
            
            Action::make('packing_list_excel')
                ->label('Packing List Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['on_board', 'in_transit', 'customs_clearance', 'delivered']))
                ->action(function () {
                    try {
                        // Create or get PackingList
                        $packingList = $this->record->packingList;
                        if (!$packingList) {
                            $packingList = PackingList::generateFromShipment($this->record);
                        }
                        
                        $excelService = app(PackingListExcelService::class);
                        $path = $excelService->generate($this->record);
                        
                        Notification::make()
                            ->success()
                            ->title('Excel Generated')
                            ->body('Packing List Excel generated successfully')
                            ->send();
                        
                        return response()->download($path);
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error Generating Excel')
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
