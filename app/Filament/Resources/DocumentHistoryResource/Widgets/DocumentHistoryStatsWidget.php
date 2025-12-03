<?php

namespace App\Filament\Resources\DocumentHistoryResource\Widgets;

use App\Models\GeneratedDocument;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentHistoryStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalDocuments = GeneratedDocument::count();
        $pdfCount = GeneratedDocument::where('format', 'pdf')->count();
        $excelCount = GeneratedDocument::where('format', 'excel')->count();
        $todayCount = GeneratedDocument::whereDate('generated_at', today())->count();
        $thisWeekCount = GeneratedDocument::whereBetween('generated_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $thisMonthCount = GeneratedDocument::whereMonth('generated_at', now()->month)->count();
        
        // Total file size
        $totalSize = GeneratedDocument::sum('file_size');
        $formattedSize = $this->formatBytes($totalSize);
        
        // Most active document type
        $mostActive = GeneratedDocument::selectRaw('document_type, COUNT(*) as count')
            ->groupBy('document_type')
            ->orderBy('count', 'desc')
            ->first();
        
        $mostActiveType = $mostActive ? $this->formatDocumentType($mostActive->document_type) : 'N/A';
        $mostActiveCount = $mostActive ? $mostActive->count : 0;

        return [
            Stat::make('Total Documents', $totalDocuments)
                ->description('All generated documents')
                ->descriptionIcon('heroicon-o-document-duplicate')
                ->color('success')
                ->chart($this->getLastSevenDaysChart()),
            
            Stat::make('PDF Documents', $pdfCount)
                ->description(round(($totalDocuments > 0 ? ($pdfCount / $totalDocuments) * 100 : 0), 1) . '% of total')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('danger'),
            
            Stat::make('Excel Documents', $excelCount)
                ->description(round(($totalDocuments > 0 ? ($excelCount / $totalDocuments) * 100 : 0), 1) . '% of total')
                ->descriptionIcon('heroicon-o-table-cells')
                ->color('success'),
            
            Stat::make('Today', $todayCount)
                ->description('Generated today')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),
            
            Stat::make('This Week', $thisWeekCount)
                ->description('Generated this week')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),
            
            Stat::make('This Month', $thisMonthCount)
                ->description('Generated this month')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('warning'),
            
            Stat::make('Total Storage', $formattedSize)
                ->description('Total file size')
                ->descriptionIcon('heroicon-o-server-stack')
                ->color('gray'),
            
            Stat::make('Most Active', $mostActiveType)
                ->description($mostActiveCount . ' documents generated')
                ->descriptionIcon('heroicon-o-fire')
                ->color('danger'),
        ];
    }

    protected function getLastSevenDaysChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = GeneratedDocument::whereDate('generated_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    protected function formatDocumentType(string $type): string
    {
        return match ($type) {
            'rfq' => 'RFQ',
            'supplier_quote' => 'Supplier Quote',
            'purchase_order' => 'Purchase Order',
            'proforma_invoice' => 'Proforma Invoice',
            'commercial_invoice' => 'Commercial Invoice',
            default => strtoupper(str_replace('_', ' ', $type)),
        };
    }

    protected static ?int $sort = -1;
}
