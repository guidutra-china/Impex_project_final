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
            // Update product selections
            $this->customerQuote->productSelections()->update([
                'is_selected_by_customer' => false,
                'selected_at' => null,
            ]);

            $this->customerQuote->productSelections()
                ->whereIn('quote_item_id', $this->selectedProducts)
                ->update([
                    'is_selected_by_customer' => true,
                    'selected_at' => now(),
                ]);

            // Create Proforma Invoice
            $proformaInvoiceService = app(ProformaInvoiceService::class);
            $proformaInvoice = $proformaInvoiceService->createFromCustomerQuoteSelection(
                $this->customerQuote,
                $this->selectedProducts
            );

            // Update customer quote status
            $this->customerQuote->update([
                'status' => 'accepted',
                'approved_at' => now(),
            ]);

            Notification::make()
                ->success()
                ->title('Selection submitted successfully!')
                ->body('Your Proforma Invoice has been created.')
                ->send();

            // Redirect to Proforma Invoice PDF
            return redirect()->route('public.proforma-invoice.show', [
                'token' => $proformaInvoice->public_token
            ]);

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
