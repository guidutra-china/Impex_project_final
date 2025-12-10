<div class="space-y-4">
    @if(!$analysis)
        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <p class="text-gray-600 dark:text-gray-400">⏳ Waiting for file analysis...</p>
        </div>
    @else
        @php
            $aiAnalysis = $analysis['ai_analysis'] ?? [];
            $documentType = $aiAnalysis['document_type'] ?? 'Unknown';
            $confidence = $aiAnalysis['confidence'] ?? 0;
            $productsCount = $aiAnalysis['products_count'] ?? 0;
            $hasImages = $analysis['has_images'] ?? false;
            $imageCount = count($analysis['images'] ?? []);
            $supplier = $aiAnalysis['supplier'] ?? null;
            $currency = $aiAnalysis['currency'] ?? 'USD';
            $suggestedTags = $aiAnalysis['suggested_tags'] ?? [];
            $columnMapping = $aiAnalysis['column_mapping'] ?? [];
        @endphp

        {{-- Document Info --}}
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                🤖 AI Analysis Complete
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Document Type:</span>
                    <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $documentType }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Confidence:</span>
                    <span class="ml-2 text-gray-900 dark:text-gray-100">{{ number_format($confidence * 100, 1) }}%</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Products Found:</span>
                    <span class="ml-2 text-gray-900 dark:text-gray-100 font-bold">{{ $productsCount }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Currency:</span>
                    <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $currency }}</span>
                </div>
            </div>
        </div>

        {{-- Supplier Info --}}
        @if($supplier && !empty($supplier['name']))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">
                    🏭 Supplier Detected
                </h3>
                <div class="space-y-1 text-sm">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Name:</span>
                        <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $supplier['name'] }}</span>
                    </div>
                    @if(!empty($supplier['email']))
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Email:</span>
                            <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $supplier['email'] }}</span>
                        </div>
                    @endif
                    @if(!empty($supplier['country']))
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Country:</span>
                            <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $supplier['country'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Images Info --}}
        @if($hasImages)
            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-2">
                    📷 Images Found
                </h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-bold text-purple-900 dark:text-purple-100">{{ $imageCount }}</span> 
                    product images will be imported
                </p>
            </div>
        @endif

        {{-- Suggested Tags --}}
        @if(!empty($suggestedTags))
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100 mb-2">
                    🏷️ Suggested Tags
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($suggestedTags as $tag)
                        <span class="px-3 py-1 bg-yellow-200 dark:bg-yellow-800 text-yellow-900 dark:text-yellow-100 rounded-full text-sm">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Column Mapping --}}
        @if(!empty($columnMapping))
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    📋 Detected Field Mapping
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    @foreach($columnMapping as $column => $mapping)
                        @php
                            $field = is_array($mapping) ? $mapping['field'] : $mapping;
                            $label = is_array($mapping) ? ($mapping['label'] ?? $column) : $column;
                            $confidence = is_array($mapping) ? ($mapping['confidence'] ?? 0) : 0;
                        @endphp
                        <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                            <div>
                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $column }}</span>
                                <span class="mx-2 text-gray-400">→</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst(str_replace('_', ' ', $field)) }}</span>
                            </div>
                            @if($confidence > 0)
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($confidence * 100) }}%
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if(!empty($aiAnalysis['notes']))
            <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-2">
                    💡 AI Notes
                </h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $aiAnalysis['notes'] }}</p>
            </div>
        @endif
    @endif
</div>
