<x-filament-panels::page>
    <form wire:submit="saveConfiguration">
        {{ $this->form }}
        
        <x-filament-actions::modals />
    </form>
    
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-cog-6-tooth"
                    class="h-5 w-5 text-gray-500 dark:text-gray-400"
                />
                <span>Ações</span>
            </div>
        </x-slot>
        
        <x-slot name="description">
            Salve suas configurações ou restaure os padrões do sistema
        </x-slot>
        
        <div class="flex gap-3 justify-end">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
