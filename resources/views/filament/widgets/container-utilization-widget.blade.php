<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            @if ($shipment)
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @php
                        $summary = $this->getSummary();
                    @endphp
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Containers</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['total_containers'] ?? 0 }}</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Sealed</div>
                        <div class="text-2xl font-bold text-green-600">{{ $summary['sealed_containers'] ?? 0 }}</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Weight Utilization</div>
                        <div class="text-2xl font-bold text-blue-600">{{ $summary['weight_utilization'] ?? 0 }}%</div>
                        <div class="text-xs text-gray-500">{{ $summary['total_weight'] ?? '0 / 0 kg' }}</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Volume Utilization</div>
                        <div class="text-2xl font-bold text-purple-600">{{ $summary['volume_utilization'] ?? 0 }}%</div>
                        <div class="text-xs text-gray-500">{{ $summary['total_volume'] ?? '0 / 0 m³' }}</div>
                    </div>
                </div>

                <!-- Container Details -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Container Utilization</h3>
                    
                    @php
                        $containers = $this->getContainerUtilization();
                    @endphp

                    @forelse ($containers as $container)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $container['container_number'] }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Type: {{ $container['container_type'] }} | 
                                        Status: <span class="badge badge-{{ $container['status'] === 'sealed' ? 'success' : 'info' }}">{{ $container['status'] }}</span> |
                                        Items: {{ $container['items_count'] }}
                                    </p>
                                </div>
                            </div>

                            <!-- Weight Progress -->
                            <div class="mb-4">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Weight</span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $container['weight_current'] }} / {{ $container['weight_max'] }} kg</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div 
                                        class="bg-blue-600 h-2 rounded-full" 
                                        style="width: {{ min($container['weight_percentage'], 100) }}%"
                                    ></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $container['weight_percentage'] }}% utilized</p>
                            </div>

                            <!-- Volume Progress -->
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Volume</span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $container['volume_current'] }} / {{ $container['volume_max'] }} m³</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div 
                                        class="bg-purple-600 h-2 rounded-full" 
                                        style="width: {{ min($container['volume_percentage'], 100) }}%"
                                    ></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $container['volume_percentage'] }}% utilized</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            No containers found for this shipment
                        </div>
                    @endforelse
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    Select a shipment to view container utilization
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
