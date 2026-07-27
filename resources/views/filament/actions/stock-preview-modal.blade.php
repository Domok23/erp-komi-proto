<div class="space-y-6">
    <x-filament::section compact>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    Production Quantity:
                </span>
                <x-filament::input.wrapper class="w-36">
                    <x-filament::input
                        type="number"
                        wire:model.live.debounce.300ms="productionQty"
                        min="0.01"
                        step="0.01"
                    />
                </x-filament::input.wrapper>
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Calculated for {{ count($materials) }} material(s)
            </span>
        </div>
    </x-filament::section>

    {{ $this->table }}
</div>
