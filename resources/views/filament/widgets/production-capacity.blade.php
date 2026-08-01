<x-filament::section class="h-full">
    <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; gap: 16px; font-family: system-ui, sans-serif;">
        <div>
            <h3 style="font-size: 14px; font-weight: 700; color: #111827; margin: 0;" class="dark:text-white">Production Capacity Utilization</h3>
            <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0 0;" class="dark:text-gray-400">Current active production runs tracking</p>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin: 12px 0; gap: 20px;">
            <!-- Circular Progress SVG -->
            <div style="position: relative; display: flex; align-items: center; justify-content: center; width: 110px; height: 110px; flex-shrink: 0;">
                <svg style="width: 100%; height: 100%; transform: rotate(-90deg);" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="transparent" class="dark:stroke-gray-700" />
                    <circle cx="50" cy="50" r="40" stroke="url(#capacity-gradient)" stroke-width="8" fill="transparent"
                            stroke-dasharray="251.2"
                            stroke-dashoffset="{{ 251.2 - (251.2 * ($utilizationRate / 100)) }}"
                            stroke-linecap="round"
                            style="transition: stroke-dashoffset 1s ease-out;" />
                    
                    <defs>
                        <linearGradient id="capacity-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#3b82f6" />
                            <stop offset="100%" stop-color="#10b981" />
                        </linearGradient>
                    </defs>
                </svg>
                <div style="position: absolute; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <span style="font-size: 20px; font-weight: 800; color: #111827;" class="dark:text-white">{{ $utilizationRate }}%</span>
                    <span style="font-size: 9px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; color: #6b7280;" class="dark:text-gray-400">Utilized</span>
                </div>
            </div>

            <!-- Stats Metadata -->
            <div style="display: flex; flex-direction: column; gap: 8px; flex-grow: 1;">
                <div>
                    <span style="font-size: 11px; color: #6b7280; display: block;" class="dark:text-gray-400">Active Runs</span>
                    <span style="font-size: 15px; font-weight: 700; color: #111827;" class="dark:text-white">{{ $activeOrdersCount }} Orders</span>
                </div>
                <div>
                    <span style="font-size: 11px; color: #6b7280; display: block;" class="dark:text-gray-400">Total Planned</span>
                    <span style="font-size: 13px; font-weight: 600; color: #374151;" class="dark:text-gray-200">{{ number_format($totalPlanned, 0) }} units</span>
                </div>
                <div>
                    <span style="font-size: 11px; color: #6b7280; display: block;" class="dark:text-gray-400">Total Completed</span>
                    <span style="font-size: 13px; font-weight: 600; color: #374151;" class="dark:text-gray-200">{{ number_format($totalCompleted, 0) }} units</span>
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid #f3f4f6; padding-top: 12px; display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #6b7280;" class="dark:border-gray-800 dark:text-gray-400">
            <span>Formula: Completed / Planned</span>
            <span style="font-weight: 600; color: #059669; display: flex; align-items: center; gap: 4px;" class="dark:text-emerald-400">
                <span style="width: 6px; height: 6px; border-radius: 9999px; background-color: #10b981; display: inline-block;"></span> Live Track
            </span>
        </div>
    </div>
</x-filament::section>
