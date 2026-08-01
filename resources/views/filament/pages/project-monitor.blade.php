<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Header Sub-Bar with Refresh & Timestamp -->
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 12px 20px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);" class="dark:bg-gray-800 dark:border-gray-700">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="display: inline-block; width: 10px; height: 10px; background-color: #10b981; border-radius: 9999px;"></span>
                <span style="font-size: 13px; font-weight: 600; color: #374151;" class="dark:text-gray-200">Live Operations Feed</span>
            </div>
            
            <div style="display: flex; align-items: center; gap: 16px; font-size: 13px;">
                <span style="color: #6b7280;" class="dark:text-gray-400">
                    Last updated: <strong style="color: #111827;" class="dark:text-white">{{ $this->lastUpdatedTime ?? now()->format('H:i:s') }}</strong>
                </span>
                
                <button wire:click="refreshPage" type="button" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background-color: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; font-size: 12px; font-weight: 600; color: #374151; cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#e5e7eb'" onmouseout="this.style.backgroundColor='#f3f4f6'" class="dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    <svg style="width: 14px; height: 14px; min-width: 14px; min-height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh Feed
                </button>
            </div>
        </div>

        <!-- KPI Stats Overview Widget -->
        <div>
            @livewire(\App\Filament\Widgets\ProjectMonitorStats::class)
        </div>

        <!-- Main Live Projects Table -->
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
