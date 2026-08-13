<style>
    .stock-preview-header-bar {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 16px !important;
        padding: 12px 16px !important;
        border-radius: 12px !important;
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        margin-bottom: 16px !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
    }
    .dark .stock-preview-header-bar,
    .fi-theme-dark .stock-preview-header-bar {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: none !important;
    }
    .stock-preview-qty-group {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 12px !important;
    }
    .stock-preview-label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #334155 !important;
        white-space: nowrap !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1.2 !important;
    }
    .dark .stock-preview-label,
    .fi-theme-dark .stock-preview-label {
        color: #f1f5f9 !important;
    }
    .stock-preview-icon-svg {
        width: 18px !important;
        height: 18px !important;
        min-width: 18px !important;
        min-height: 18px !important;
        max-width: 18px !important;
        max-height: 18px !important;
        flex-shrink: 0 !important;
        color: #f59e0b !important;
        display: inline-block !important;
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
        outline: none !important;
        box-shadow: none !important;
    }
    .stock-preview-input-box {
        width: 90px !important;
        flex-shrink: 0 !important;
    }
</style>

<div class="space-y-6">
    <div class="stock-preview-header-bar" wire:key="stock-preview-header-bar">
        <div class="stock-preview-qty-group">
            <span class="stock-preview-label">
                <x-heroicon-m-cube class="stock-preview-icon-svg" />
                <span>Production Quantity:</span>
            </span>
            <div class="stock-preview-input-box">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="number"
                        wire:model.live.debounce.300ms="productionQty"
                        min="0.01"
                        step="0.01"
                        style="text-align: center; font-family: monospace; font-weight: 700;"
                    />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div wire:key="materials-count-badge">
            <x-filament::badge color="warning" icon="heroicon-m-cube">
                {{ count($materials) }} Material(s)
            </x-filament::badge>
        </div>
    </div>

    <div wire:key="stock-preview-table-container">
        {{ $this->table }}
    </div>
</div>


