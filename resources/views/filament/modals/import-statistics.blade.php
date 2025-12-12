<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Items</div>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['selected'] }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Selected for Import</div>
            <div class="text-xs text-gray-500 mt-1">
                {{ $stats['total'] > 0 ? round(($stats['selected'] / $stats['total']) * 100, 1) : 0 }}% of total
            </div>
        </div>
    </div>
    
    <div>
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Duplicate Detection</h4>
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['new'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">New Products</div>
            </div>
            
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['duplicate'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Duplicates</div>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['similar'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Similar</div>
            </div>
        </div>
    </div>
    
    <div>
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Data Quality</h4>
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['with_errors'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Items with Errors</div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $stats['total'] - $stats['with_errors'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Valid Items</div>
            </div>
        </div>
    </div>
    
    <div>
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Photos</h4>
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['with_photos'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Photos Extracted</div>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['missing_photos'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Missing Photos</div>
            </div>
        </div>
    </div>
    
    @if($stats['total'] > 0)
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Summary</h4>
        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
            <li>• {{ round(($stats['new'] / $stats['total']) * 100, 1) }}% are new products</li>
            <li>• {{ round(($stats['duplicate'] / $stats['total']) * 100, 1) }}% are duplicates</li>
            <li>• {{ round((($stats['total'] - $stats['with_errors']) / $stats['total']) * 100, 1) }}% passed validation</li>
            <li>• {{ round(($stats['with_photos'] / $stats['total']) * 100, 1) }}% have photos</li>
        </ul>
    </div>
    @endif
</div>
