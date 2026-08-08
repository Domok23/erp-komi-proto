<x-filament::section
    heading="Production Capacity Utilization"
    description="Current active production runs tracking"
    class="h-full"
>
    <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; gap: 16px; font-family: inherit;">

        <div style="display: flex; align-items: center; justify-content: space-between; flex-grow: 1; gap: 20px;">
            <!-- Circular Progress SVG -->
            <div style="position: relative; display: flex; align-items: center; justify-content: center; width: 140px; height: 140px; flex-shrink: 0;">
                <svg style="width: 100%; height: 100%; transform: rotate(-90deg);" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="transparent" class="prod-cap-circle-bg" />
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
                    <span class="prod-cap-title" style="font-size: 26px; font-weight: 800; color: #111827;">{{ $utilizationRate }}%</span>
                    <span class="prod-cap-subtext" style="font-size: 10px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; color: #6b7280;">Utilized</span>
                </div>
            </div>

            <!-- Stats Metadata -->
            <div style="display: flex; flex-direction: column; justify-content: center; gap: 10px; flex-grow: 1;">
                <div>
                    <span class="prod-cap-subtext" style="font-size: 11px; color: #6b7280; display: block; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Active Runs</span>
                    <span class="prod-cap-title" style="font-size: 15px; font-weight: 700; color: #111827;">{{ $activeOrdersCount }} Orders</span>
                    <span class="prod-cap-subtext" style="font-size: 11px; color: #6b7280; display: block;">{{ $runningOrdersCount }} Running · {{ $plannedOrdersCount }} Planned</span>
                </div>
                <div>
                    <span class="prod-cap-subtext" style="font-size: 11px; color: #6b7280; display: block; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Units Completed / Planned</span>
                    <span class="prod-cap-text" style="font-size: 13px; font-weight: 600; color: #374151;">{{ number_format($totalCompleted, 0) }} / {{ number_format($totalPlanned, 0) }}</span>
                </div>
                <div>
                    <span class="prod-cap-subtext" style="font-size: 11px; color: #6b7280; display: block; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Remaining Units</span>
                    <span class="prod-cap-text" style="font-size: 13px; font-weight: 600; color: #374151;">{{ number_format($remainingUnits, 0) }} units</span>
                </div>
            </div>
        </div>

        <!-- Active Orders Execution Ratio Bar -->
        <div style="display: flex; flex-direction: column; gap: 4px; margin-top: 4px;">
            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                <span class="prod-cap-subtext">Active Orders Execution ({{ $runningOrdersCount }}/{{ $activeOrdersCount }} Running)</span>
                <span class="prod-cap-title" style="font-weight: 700;">{{ $runningOrderRate }}%</span>
            </div>
            <div style="width: 100%; height: 8px; background-color: #e5e7eb; border-radius: 9999px; overflow: hidden;" class="prod-cap-circle-bg">
                <div style="width: {{ $runningOrderRate }}%; height: 100%; background: linear-gradient(90deg, #3b82f6, #10b981); border-radius: 9999px; transition: width 0.5s ease;"></div>
            </div>
        </div>

        <div class="prod-cap-footer" style="margin-top: auto; border-top: 1px solid #f3f4f6; padding-top: 12px; display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #6b7280;">
            <span class="prod-cap-subtext">Formula: Completed / Planned</span>
            <span style="font-weight: 600; color: #059669; display: flex; align-items: center; gap: 4px;">
                <span style="width: 6px; height: 6px; border-radius: 9999px; background-color: #10b981; display: inline-block;"></span> Live Track
            </span>
        </div>
    </div>

    <style>
        .dark .prod-cap-title {
            color: #f9fafb !important;
        }
        .dark .prod-cap-text {
            color: #e5e7eb !important;
        }
        .dark .prod-cap-subtext {
            color: #9ca3af !important;
        }
        .dark .prod-cap-footer {
            border-top-color: #374151 !important;
        }
        .dark .prod-cap-circle-bg {
            stroke: #374151 !important;
        }
    </style>
</x-filament::section>
