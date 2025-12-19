<?php

namespace App\Filament\Portal\Resources\CustomerQuoteResource\Pages;

use App\Filament\Portal\Resources\CustomerQuoteResource;
use App\Services\CustomerQuoteService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerQuote extends ViewRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Update status to 'viewed' when customer opens the quote
        if ($this->record->status === 'sent') {
            $this->record->update(['status' => 'viewed']);
        }
    }

    public function getView(): string
    {
        return 'filament.portal.pages.customer-quote-livewire-wrapper';
    }

    protected function getHeaderActions(): array
    {
        return [
            // Select Option action removed - product selection now handled by Livewire component
        ];
    }
}
