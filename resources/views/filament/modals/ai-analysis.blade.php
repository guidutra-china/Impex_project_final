<div class="space-y-4">
    @if(!empty($analysis))
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
            <h3 class="text-lg font-semibold mb-2">Document Analysis</h3>
            
            <dl class="grid grid-cols-2 gap-4">
                @if(!empty($analysis['document_type']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Document Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $analysis['document_type'] }}</dd>
                    </div>
                @endif
                
                @if(!empty($analysis['confidence']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Confidence</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ round($analysis['confidence'] * 100, 1) }}%</dd>
                    </div>
                @endif
                
                @if(!empty($analysis['products_count']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Products Found</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $analysis['products_count'] }}</dd>
                    </div>
                @endif
                
                @if(!empty($analysis['currency']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Currency</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $analysis['currency'] }}</dd>
                    </div>
                @endif
                
                @if(!empty($analysis['has_images']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Has Images</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Yes
                            </span>
                        </dd>
                    </div>
                @endif
                
                @if(!empty($analysis['start_row']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data Starts at Row</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $analysis['start_row'] }}</dd>
                    </div>
                @endif
            </dl>
            
            @if(!empty($analysis['supplier']))
                <div class="mt-4">
                    <h4 class="text-sm font-semibold mb-2">Supplier Information</h4>
                    <dl class="grid grid-cols-2 gap-2">
                        @if(!empty($analysis['supplier']['name']))
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Name</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $analysis['supplier']['name'] }}</dd>
                            </div>
                        @endif
                        @if(!empty($analysis['supplier']['email']))
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Email</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $analysis['supplier']['email'] }}</dd>
                            </div>
                        @endif
                        @if(!empty($analysis['supplier']['country']))
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Country</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $analysis['supplier']['country'] }}</dd>
                            </div>
                        @endif
                        @if(!empty($analysis['supplier']['phone']))
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Phone</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $analysis['supplier']['phone'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
            
            @if(!empty($analysis['suggested_tags']))
                <div class="mt-4">
                    <h4 class="text-sm font-semibold mb-2">Suggested Tags</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($analysis['suggested_tags'] as $tag)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
            
            @if(!empty($analysis['notes']))
                <div class="mt-4">
                    <h4 class="text-sm font-semibold mb-2">AI Notes</h4>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $analysis['notes'] }}</p>
                </div>
            @endif
        </div>
        
        @if(!empty($mapping))
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                <h3 class="text-lg font-semibold mb-2">Column Mapping</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Column</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Label</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Maps To</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Confidence</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($mapping as $column => $info)
                                <tr>
                                    <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $column }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $info['label'] ?? 'N/A' }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">
                                            {{ $info['field'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        @if(!empty($info['confidence']))
                                            {{ round($info['confidence'] * 100, 0) }}%
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            No AI analysis available yet.
        </div>
    @endif
</div>
