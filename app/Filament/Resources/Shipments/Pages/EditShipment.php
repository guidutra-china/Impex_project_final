<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\CommercialInvoice;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_commercial_invoice')
                ->label('Generate Commercial Invoice')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['on_board', 'in_transit', 'delivered']))
                ->disabled(fn () => $this->record->commercialInvoices()->exists())
                ->tooltip(fn () => $this->record->commercialInvoices()->exists() 
                    ? 'Commercial Invoice already exists for this shipment' 
                    : 'Generate Commercial Invoice from shipment data')
                ->action(function () {
                    try {
                        $invoice = CommercialInvoice::generateFromShipment($this->record);
                        
                        Notification::make()
                            ->success()
                            ->title('Commercial Invoice Created')
                            ->body("Invoice {$invoice->invoice_number} created successfully")
                            ->send();
                        
                        // Redirect to edit the new invoice
                        return redirect()->route('filament.admin.resources.commercial-invoices.edit', $invoice);
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error Creating Commercial Invoice')
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
