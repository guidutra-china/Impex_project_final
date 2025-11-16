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

    protected static string | UnitEnum | null $navigationGroup = 'Quotations';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Quote Comparison';

    protected static ?string $title = 'Quote Comparison';

    public ?int $orderId = null;

    public ?Order $order = null;

    public ?array $comparison = null;

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
                'supplierQuotes.items.orderItem.product',
            ])->findOrFail($this->orderId);

            $comparisonService = new QuoteComparisonService();
            $this->comparison = $comparisonService->compareQuotes($this->order);
        }
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->order) {
            return "Quote Comparison - Order #{$this->order->order_number}";
        }

        return 'Quote Comparison';
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
        ];
    }
}