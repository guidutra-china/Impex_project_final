<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Import Preview</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Review the products below before importing. You can edit individual items, select which ones to import, and choose the appropriate action for duplicates.
            </p>
            
            <div class="grid grid-cols-4 gap-4 mb-6">
                @php
                    $total = $this->record->previewItems()->count();
                    $selected = $this->record->previewItems()->where('selected', true)->count();
                    $duplicates = $this->record->previewItems()->where('duplicate_status', 'duplicate')->count();
                    $errors = $this->record->previewItems()->where('has_errors', true)->count();
                @endphp
                
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $total }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Items</div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $selected }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Selected</div>
                </div>
                
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $duplicates }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Duplicates</div>
                </div>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $errors }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Errors</div>
                </div>
            </div>
        </div>
        
        {{ $this->table }}
    </div>
</x-filament-panels::page>
