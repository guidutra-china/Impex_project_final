<?php

namespace App\Livewire\Portal;

use App\Models\CustomerQuote;
use App\Models\CustomerQuoteProductSelection;
use App\Services\ProformaInvoiceService;
use Filament\Notifications\Notification;
use Livewire\Component;

class CustomerQuoteSelection extends Component
{
    public CustomerQuote $customerQuote;
    public array $selectedProducts = [];
    public bool $isSubmitting = false;

    public function mount(CustomerQuote $customerQuote)
    {
        // Load order without scope to avoid conflicts
        $customerQuote->load(['order' => function($query) {
            $query->withoutGlobalScopes();
        }]);
        
        $this->customerQuote = $customerQuote;
        
        // Load already selected products
        $this->selectedProducts = $customerQuote->productSelections()
            ->where('is_selected_by_customer', true)
            ->pluck('quote_item_id')
            ->toArray();
    }

    public function toggleProduct($quoteItemId)
    {
        if (in_array($quoteItemId, $this->selectedProducts)) {
            $this->selectedProducts = array_diff($this->selectedProducts, [$quoteItemId]);
        } else {
            $this->selectedProducts[] = $quoteItemId;
        }
    }

    public function submitSelection()
    {
        if (empty($this->selectedProducts)) {
            Notification::make()
                ->warning()
                ->title('No products selected')
                ->body('Please select at least one product before submitting.')
                ->send();
            return;
        }

        $this->isSubmitting = true;

        try {
            // Reload CustomerQuote with order (without scope)
            $customerQuote = CustomerQuote::with(['order' => function($query) {
                $query->withoutGlobalScopes();
            }])->findOrFail($this->customerQuote->id);
            
            // Update product selections
            $customerQuote->productSelections()->update([
                'is_selected_by_customer' => false,
                'selected_at' => null,
            ]);

            $customerQuote->productSelections()
                ->whereIn('quote_item_id', $this->selectedProducts)
                ->update([
                    'is_selected_by_customer' => true,
                    'selected_at' => now(),
                ]);

            // Create Proforma Invoice
            $proformaInvoiceService = app(ProformaInvoiceService::class);
            $proformaInvoice = $proformaInvoiceService->createFromCustomerQuoteSelection(
                $customerQuote,
                $this->selectedProducts
            );

            // Update customer quote status
            $customerQuote->update([
                'status' => 'accepted',
                'approved_at' => now(),
            ]);

            Notification::make()
                ->success()
                ->title('Selection submitted successfully!')
                ->body('Proforma Invoice #' . $proformaInvoice->proforma_number . ' has been created in draft status.')
                ->send();

            // Reload the page to clear selections
            $this->selectedProducts = [];
            $this->isSubmitting = false;

        } catch (\Exception $e) {
            $this->isSubmitting = false;
            
            Notification::make()
                ->danger()
                ->title('Error submitting selection')
                ->body('An error occurred. Please try again or contact support.')
                ->send();
                
            \Log::error('Error creating proforma invoice from customer quote selection', [
                'customer_quote_id' => $this->customerQuote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.portal.customer-quote-selection');
    }
}
