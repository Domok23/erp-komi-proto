<div class="space-y-6">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.875rem 1rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background-color: rgba(249, 250, 251, 0.8); margin-bottom: 0.5rem;" class="dark:bg-gray-800/40 dark:border-gray-700" wire:key="stock-preview-header-bar">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <label style="font-size: 0.875rem; font-weight: 600; white-space: nowrap;" class="text-gray-700 dark:text-gray-200">
                Production Quantity:
            </label>
            <div style="width: 7rem;">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="number"
                        wire:model.live.debounce.300ms="productionQty"
                        wire:key="prod-qty-input"
                        min="0.01"
                        step="0.01"
                        style="text-align: center; font-family: monospace;"
                    />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div wire:key="materials-count-badge">
            <x-filament::badge color="gray" icon="heroicon-m-cube">
                {{ count($materials) }} Material(s)
            </x-filament::badge>
        </div>
    </div>

    <div wire:key="stock-preview-table-container">
        {{ $this->table }}
    </div>
</div>
