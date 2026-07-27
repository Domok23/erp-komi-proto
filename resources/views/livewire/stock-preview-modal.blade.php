<div class="space-y-4">
    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Production Qty Multiplier:</label>
            <input
                type="number"
                wire:model.live.debounce.300ms="productionQty"
                min="0.01"
                step="0.01"
                class="w-32 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm font-mono"
            />
        </div>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            Calculated for {{ count($materials) }} material(s)
        </span>
    </div>

    {{ $this->table }}
</div>
