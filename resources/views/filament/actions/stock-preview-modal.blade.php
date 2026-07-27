<div class="space-y-4">
    <div class="flex items-center gap-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Production Quantity:</label>
        <input
            type="number"
            wire:model.live.debounce.300ms="productionQty"
            min="0.01"
            step="0.01"
            class="w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 sm:text-sm"
        />
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th scope="col" class="p-3 text-center">
                        <input
                            type="checkbox"
                            wire:model.live="selectAll"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                    </th>
                    <th scope="col" class="px-4 py-3">Material</th>
                    <th scope="col" class="px-4 py-3 text-right">Current Stock</th>
                    <th scope="col" class="px-4 py-3 text-right">Required</th>
                    <th scope="col" class="px-4 py-3 text-right">To Buy</th>
                    <th scope="col" class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($previews as $preview)
                    <tr class="bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <td class="p-3 text-center">
                            <input
                                type="checkbox"
                                wire:model.live="selected"
                                value="{{ $preview['materialId'] }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $preview['materialName'] }}
                            @if($preview['materialCode'])
                                <span class="text-xs text-gray-400">({{ $preview['materialCode'] }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format($preview['currentStock'], 2) }} {{ $preview['unit'] }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format($preview['required'], 2) }} {{ $preview['unit'] }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold {{ $preview['toBuy'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500' }}">
                            {{ number_format($preview['toBuy'], 2) }} {{ $preview['unit'] }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                @if($preview['status'] === 'sufficient') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300
                                @elseif($preview['status'] === 'partial') bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300
                                @else bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300
                                @endif">
                                {{ ucfirst($preview['status']) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                            No materials found for preview.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <button
            type="button"
            wire:click="reserveSelected"
            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            @if(empty($selected)) disabled @endif
        >
            Reserve Selected
        </button>
        <button
            type="button"
            wire:click="createPOSelected"
            class="inline-flex items-center rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 disabled:opacity-50 disabled:cursor-not-allowed"
            @if(empty($selected)) disabled @endif
        >
            Create PO for Selected
        </button>
    </div>
</div>
