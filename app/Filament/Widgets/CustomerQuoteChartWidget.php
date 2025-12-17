<?php

namespace App\Filament\Widgets;

use App\Models\CustomerQuote;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CustomerQuoteChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Customer Quotes Over Time';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = $this->getQuotesPerMonth();

        return [
            'datasets' => [
                [
                    'label' => 'Quotes Created',
                    'data' => $data['totals'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
                [
                    'label' => 'Quotes Accepted',
                    'data' => $data['accepted'],
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ],
            ],
            'labels' => $data['months'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function getQuotesPerMonth(): array
    {
        $now = Carbon::now();
        $months = [];
        $totals = [];
        $accepted = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $months[] = $month->format('M Y');

            $total = CustomerQuote::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            $acceptedCount = CustomerQuote::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->where('status', 'accepted')
                ->count();

            $totals[] = $total;
            $accepted[] = $acceptedCount;
        }

        return [
            'months' => $months,
            'totals' => $totals,
            'accepted' => $accepted,
        ];
    }
}
