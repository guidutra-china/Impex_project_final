<?php

namespace App\Filament\Widgets;

use App\Models\CustomerQuote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class CustomerQuoteStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Total quotes
        $totalQuotes = CustomerQuote::count();

        // Quotes by status
        $statusCounts = CustomerQuote::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Conversion rate (accepted / sent)
        $sent = $statusCounts->get('sent', 0) + $statusCounts->get('viewed', 0) + $statusCounts->get('accepted', 0);
        $accepted = $statusCounts->get('accepted', 0);
        $conversionRate = $sent > 0 ? round(($accepted / $sent) * 100, 1) : 0;

        // Average response time (from sent to viewed/accepted)
        $avgResponseTime = CustomerQuote::whereNotNull('sent_at')
            ->where(function ($query) {
                $query->whereNotNull('viewed_at')
                    ->orWhereNotNull('approved_at');
            })
            ->get()
            ->map(function ($quote) {
                $responseTime = $quote->viewed_at ?? $quote->approved_at;
                if ($responseTime && $quote->sent_at) {
                    return $quote->sent_at->diffInHours($responseTime);
                }
                return null;
            })
            ->filter()
            ->avg();

        $avgResponseTimeFormatted = $avgResponseTime 
            ? ($avgResponseTime < 24 
                ? round($avgResponseTime, 1) . ' hrs' 
                : round($avgResponseTime / 24, 1) . ' days')
            : 'N/A';

        // Pending quotes (sent but not viewed)
        $pending = CustomerQuote::where('status', 'sent')
            ->whereNull('viewed_at')
            ->count();

        return [
            Stat::make('Total Quotes', $totalQuotes)
                ->description('All customer quotes')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Conversion Rate', $conversionRate . '%')
                ->description($accepted . ' accepted out of ' . $sent . ' sent')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($conversionRate >= 50 ? 'success' : ($conversionRate >= 25 ? 'warning' : 'danger')),

            Stat::make('Avg Response Time', $avgResponseTimeFormatted)
                ->description('From sent to viewed/accepted')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Pending Quotes', $pending)
                ->description('Sent but not yet viewed')
                ->descriptionIcon('heroicon-m-envelope')
                ->color($pending > 0 ? 'warning' : 'success'),
        ];
    }
}
