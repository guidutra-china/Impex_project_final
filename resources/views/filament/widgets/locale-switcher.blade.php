<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Language') }}
                </span>
            </div>
            
            <div class="flex gap-2">
                @foreach($this->getAvailableLocales() as $localeCode => $localeName)
                    <button
                        wire:click="switchLocale('{{ $localeCode }}')"
                        class="px-3 py-1.5 text-sm rounded-lg transition-colors
                            {{ $this->getCurrentLocale() === $localeCode 
                                ? 'bg-primary-600 text-white font-semibold' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' 
                            }}"
                    >
                        {{ $localeName }}
                    </button>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
