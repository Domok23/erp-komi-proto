<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Native Filament Header Section -->
        <x-filament::section compact>
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <x-filament::badge color="success" size="sm" icon="heroicon-m-bolt">
                        Live Operations Feed
                    </x-filament::badge>
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px; font-size: 13px;">
                    <span class="text-gray-500 dark:text-gray-400">
                        Last updated: <strong class="text-gray-950 dark:text-white">{{ $this->lastUpdatedTime ?? now()->format('H:i:s') }}</strong>
                    </span>
                    
                    <x-filament::button
                        wire:click="refreshPage"
                        color="gray"
                        icon="heroicon-m-arrow-path"
                        size="xs"
                    >
                        Refresh Feed
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

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
