@php
    $record = $getRecord();
    $produced = $record->produced_qty ?? 0;
    $target = $record->target_qty ?? 0;
    $percent = $target > 0 ? min(100, round(($produced / $target) * 100, 1)) : 0;
    
    $barBg = match (true) {
        $percent >= 100 => '#10b981',
        $percent >= 50 => '#2563eb',
        $percent > 0 => '#f59e0b',
        default => '#9ca3af',
    };
@endphp

<div style="display: flex; flex-direction: column; gap: 4px; width: 140px; padding: 4px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 700; color: #374151;" class="dark:text-gray-200">
        <span>{{ number_format($percent, 0) }}%</span>
        <span style="color: #6b7280; font-weight: 500; font-size: 10px;">({{ number_format($produced) }}/{{ number_format($target) }})</span>
    </div>
    <div style="width: 100%; background-color: #e5e7eb; border-radius: 9999px; height: 8px; overflow: hidden;" class="dark:bg-gray-700">
        <div style="background-color: {{ $barBg }}; height: 8px; border-radius: 9999px; width: {{ $percent }}%; transition: width 0.3s;"></div>
    </div>
</div>
