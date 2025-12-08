<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Services\QuoteComparisonService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class QuoteComparison extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public function getView(): string
    {
        return 'filament.pages.quote-comparison';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.sales_quotations');
    }

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('navigation.quote_comparison');
    }

    public ?int $orderId = null;

    public ?Order $order = null;

    public ?array $comparison = null;

    public ?array $summary = null;

    public array $selectedQuotes = [];

    public function mount(): void
    {
        $this->orderId = request()->query('order');

        if ($this->orderId) {
            $this->order = Order::with([
                'currency',
                'customer',
                'items.product',
                'supplierQuotes.supplier',
                'supplierQuotes.currency',
                'supplierQuotes.items.product',
            ])->findOrFail($this->orderId);

            $comparisonService = new QuoteComparisonService();
            $this->comparison = $comparisonService->compareQuotes($this->order);
            $this->summary = $comparisonService->getSummary($this->order);
            
            // Select all quotes by default (max 4)
            $this->selectedQuotes = collect($this->comparison['overall']['all_quotes'] ?? [])
                ->take(4)
                ->pluck('quote_id')
                ->toArray();
        }
    }

    public function toggleQuote(int $quoteId): void
    {
        if (in_array($quoteId, $this->selectedQuotes)) {
            // Deselect (but keep at least 1)
            if (count($this->selectedQuotes) > 1) {
                $this->selectedQuotes = array_values(array_diff($this->selectedQuotes, [$quoteId]));
            }
        } else {
            // Select (max 4)
            if (count($this->selectedQuotes) < 4) {
                $this->selectedQuotes[] = $quoteId;
            }
        }
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->order) {
            return __('navigation.quote_comparison') . " - Order #{$this->order->order_number}";
        }

        return __('navigation.quote_comparison');
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getTitle();
    }

    protected function getViewData(): array
    {
        return [
            'order' => $this->order,
            'comparison' => $this->comparison,
            'summary' => $this->summary,
            'selectedQuotes' => $this->selectedQuotes,
        ];
    }
}