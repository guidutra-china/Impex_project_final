<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}
        </x-filament-panels::form>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
