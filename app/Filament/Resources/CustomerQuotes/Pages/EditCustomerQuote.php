<?php

namespace App\Filament\Resources\CustomerQuotes\Pages;

use App\Filament\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Services\CustomerQuoteService;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomerQuote extends EditRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_from_supplier_quotes')
                ->label('Generate from Supplier Quotes')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn () => $this->record->items()->count() === 0)
                ->form([
                    CheckboxList::make('supplier_quote_ids')
                        ->label('Select Supplier Quotes to Include')
                        ->options(function () {
                            return \App\Models\SupplierQuote::where('order_id', $this->record->order_id)
                                ->with('supplier')
                                ->get()
                                ->mapWithKeys(function ($quote) {
                                    return [
                                        $quote->id => "{$quote->supplier->name} - {$quote->quote_number} (" . 
                                            money($quote->total_price_after_commission, $quote->currency?->code ?? 'USD') . ")"
                                    ];
                                });
                        })
                        ->required()
                        ->columns(1)
                        ->helperText('Select which supplier quotes to include as options for the customer'),
                ])
                ->action(function (array $data, CustomerQuoteService $service) {
                    $this->handleGenerateFromSupplierQuotes($data, $service);
                }),

            Action::make('view')
                ->label('View Quote')
                ->icon('heroicon-o-eye')
                ->url(fn () => $this->getResource()::getUrl('view', ['record' => $this->record])),
            
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // No need to update created_by on edit
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    /**
     * Handle generating customer quote items from supplier quotes
     */
    protected function handleGenerateFromSupplierQuotes(array $data, CustomerQuoteService $service)
    {
        try {
            if (empty($data['supplier_quote_ids'])) {
                Notification::make()
                    ->warning()
                    ->title('No Supplier Quotes Selected')
                    ->body('Please select at least one supplier quote to generate options.')
                    ->send();
                return;
            }

            // Check if items already exist
            if ($this->record->items()->count() > 0) {
                Notification::make()
                    ->warning()
                    ->title('Items Already Exist')
                    ->body('This customer quote already has items. Delete them first to regenerate.')
                    ->send();
                return;
            }

            // Get supplier quotes
            $supplierQuotes = \App\Models\SupplierQuote::whereIn('id', $data['supplier_quote_ids'])
                ->where('order_id', $this->record->order_id)
                ->with(['supplier', 'items', 'items.product'])
                ->get();

            if ($supplierQuotes->isEmpty()) {
                Notification::make()
                    ->warning()
                    ->title('No Valid Supplier Quotes Found')
                    ->body('The selected supplier quotes could not be found.')
                    ->send();
                return;
            }

            // Create customer quote items
            $displayOrder = 1;
            foreach ($supplierQuotes as $supplierQuote) {
                $this->createCustomerQuoteItem($supplierQuote, $displayOrder++);
            }
            
            Notification::make()
                ->success()
                ->title('Customer Quote Generated')
                ->body(count($supplierQuotes) . ' supplier quote(s) added as options for the customer.')
                ->send();

            // Refresh the record to show new items
            $this->record->refresh();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error Generating Quote')
                ->body($e->getMessage())
                ->send();

            \Log::error('Error generating customer quote from supplier quotes', [
                'customer_quote_id' => $this->record->id,
                'supplier_quote_ids' => $data['supplier_quote_ids'] ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Create a customer quote item from a supplier quote
     */
    protected function createCustomerQuoteItem(\App\Models\SupplierQuote $supplierQuote, int $displayOrder)
    {
        $order = $this->record->order;
        $commissionType = $order->commission_type ?? 'embedded';
        
        // Generate display name
        $displayName = 'Option ' . chr(64 + $displayOrder); // A, B, C...

        // Calculate prices based on commission type
        if ($commissionType === 'separate') {
            $pricesBefore = $supplierQuote->total_price_before_commission;
            $commission = $supplierQuote->commission_amount;
            $pricesAfter = $supplierQuote->total_price_after_commission;
        } else {
            // Embedded: Show only final price
            $pricesBefore = $supplierQuote->total_price_after_commission;
            $commission = 0;
            $pricesAfter = $supplierQuote->total_price_after_commission;
        }

        // Extract highlights
        $highlights = [];
        if ($supplierQuote->payment_terms) {
            $highlights[] = "Payment: {$supplierQuote->payment_terms}";
        }
        if ($supplierQuote->incoterm) {
            $highlights[] = "Incoterm: {$supplierQuote->incoterm}";
        }
        if ($supplierQuote->valid_until) {
            $highlights[] = "Valid until: {$supplierQuote->valid_until->format('Y-m-d')}";
        }

        // Format delivery time
        $deliveryTime = null;
        if ($supplierQuote->lead_time_days) {
            $days = $supplierQuote->lead_time_days;
            if ($days < 7) {
                $deliveryTime = "{$days} days";
            } elseif ($days < 30) {
                $weeks = ceil($days / 7);
                $deliveryTime = "{$weeks} " . ($weeks === 1 ? 'week' : 'weeks');
            } else {
                $months = ceil($days / 30);
                $deliveryTime = "{$months} " . ($months === 1 ? 'month' : 'months');
            }
        }

        return \App\Models\CustomerQuoteItem::create([
            'customer_quote_id' => $this->record->id,
            'supplier_quote_id' => $supplierQuote->id,
            'display_name' => $displayName,
            'price_before_commission' => $pricesBefore,
            'commission_amount' => $commission,
            'price_after_commission' => $pricesAfter,
            'delivery_time' => $deliveryTime,
            'moq' => $supplierQuote->moq,
            'highlights' => !empty($highlights) ? implode("\n", $highlights) : null,
            'display_order' => $displayOrder,
        ]);
    }
}
