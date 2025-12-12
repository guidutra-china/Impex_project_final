<div class="space-y-4">
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h4 class="font-semibold text-yellow-800 dark:text-yellow-200">
                {{ $preview->isDuplicate() ? 'Duplicate Detected' : 'Similar Product Found' }}
            </h4>
        </div>
        <p class="text-sm text-yellow-700 dark:text-yellow-300">
            Similarity: {{ $preview->similarity_score }}%
        </p>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div class="space-y-2">
            <h5 class="font-semibold text-gray-900 dark:text-gray-100">Import Data (New)</h5>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                <div>
                    <span class="text-xs text-gray-500">SKU:</span>
                    <span class="text-sm font-medium">{{ $preview->sku ?: 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Name:</span>
                    <span class="text-sm font-medium">{{ $preview->name }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Price:</span>
                    <span class="text-sm font-medium">{{ $preview->formatted_price }}</span>
                </div>
                @if($preview->brand)
                <div>
                    <span class="text-xs text-gray-500">Brand:</span>
                    <span class="text-sm font-medium">{{ $preview->brand }}</span>
                </div>
                @endif
            </div>
        </div>
        
        <div class="space-y-2">
            <h5 class="font-semibold text-gray-900 dark:text-gray-100">Existing in Database</h5>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                <div>
                    <span class="text-xs text-gray-500">SKU:</span>
                    <span class="text-sm font-medium">{{ $existing->sku ?: 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Name:</span>
                    <span class="text-sm font-medium">{{ $existing->name }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Price:</span>
                    <span class="text-sm font-medium">${{ number_format($existing->price / 100, 2) }}</span>
                </div>
                @if($existing->brand)
                <div>
                    <span class="text-xs text-gray-500">Brand:</span>
                    <span class="text-sm font-medium">{{ $existing->brand }}</span>
                </div>
                @endif
                <div>
                    <span class="text-xs text-gray-500">Last Updated:</span>
                    <span class="text-sm font-medium">{{ $existing->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
    </div>
    
    @if(!empty($preview->differences))
    <div class="mt-4">
        <h5 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Differences Detected:</h5>
        <div class="space-y-1">
            @foreach($preview->differences as $field => $diff)
            <div class="flex items-center gap-2 text-sm">
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                <span class="text-red-600 dark:text-red-400">{{ $diff['existing'] ?? 'N/A' }}</span>
                <span class="text-gray-400">→</span>
                <span class="text-green-600 dark:text-green-400">{{ $diff['new'] ?? 'N/A' }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
