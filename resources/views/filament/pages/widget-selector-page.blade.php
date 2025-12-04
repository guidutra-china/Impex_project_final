@php
    use Filament\Support\Enums\MaxWidth;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Seção de Widgets Disponíveis -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Widgets Disponíveis
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($this->availableWidgets as $widget)
                    <label class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition">
                        <input
                            type="checkbox"
                            wire:model="selectedWidgets"
                            value="{{ $widget['id'] }}"
                            class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                        <div class="ml-3">
                            <h3 class="font-medium text-gray-900 dark:text-white">
                                {{ $widget['title'] }}
                            </h3>
                            @if ($widget['description'])
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $widget['description'] }}
                                </p>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Seção de Ordem dos Widgets -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Ordem dos Widgets
            </h2>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Arraste os widgets abaixo para reordenar como eles aparecem no seu dashboard.
            </p>

            <div class="space-y-2" wire:sortable="updateWidgetOrder">
                @forelse ($this->selectedWidgets as $widgetId)
                    @php
                        $widget = collect($this->availableWidgets)->firstWhere('id', $widgetId);
                    @endphp

                    @if ($widget)
                        <div
                            wire:sortable.item="{{ $widgetId }}"
                            wire:key="widget-{{ $widgetId }}"
                            class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-lg cursor-move hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 transition"
                        >
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $widget['title'] }}
                            </span>
                        </div>
                    @endif
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                        Selecione widgets acima para reordenar
                    </p>
                @endforelse
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="flex gap-3 justify-end">
            <button
                type="button"
                wire:click="resetToDefault"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition"
            >
                Resetar para Padrão
            </button>

            <button
                type="button"
                wire:click="saveConfiguration"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-primary-600 rounded-lg hover:bg-primary-700 dark:bg-primary-700 dark:hover:bg-primary-800 transition"
            >
                Salvar Configuração
            </button>
        </div>
    </div>
</x-filament-panels::page>
