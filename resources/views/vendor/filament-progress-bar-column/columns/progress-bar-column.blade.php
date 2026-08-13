@php
    $currentValue = $getState();
    $maxValue = $getMaxValue();
    $percentage = $getProgressPercentage();
    $label = $getProgressLabel();

    $color = match (true) {
        $percentage > 50 => '#10b981', // Hijau (>50%)
        $percentage > 25 => '#f59e0b', // Kuning/Oranye (26%-50%)
        default          => '#ef4444', // Merah (1%-25%)
    };
@endphp

<div
    {{
        $attributes
            ->merge($getExtraAttributes(), escape: false)
            ->class(['fi-ta-text block w-full'])
    }}
    style="font-size: 14px; line-height: 1.25rem; min-width: 120px;"
>
    <div style="display: flex; flex-direction: column; gap: 6px; width: 100%; padding: 4px 0;">
        <div
            style="width: 100%; height: 6px; background-color: #e5e7eb; border-radius: 9999px; overflow: hidden;"
            class="dark:bg-gray-700"
            role="progressbar"
            aria-valuenow="{{ $currentValue }}"
            aria-valuemin="0"
            aria-valuemax="{{ $maxValue ?? 100 }}"
            aria-label="{{ $label }}"
        >
            <div
                style="height: 100%; border-radius: 9999px; width: {{ max(0, min(100, $percentage)) }}%; background-color: {{ $color }}; transition: width 0.3s;"
            ></div>
        </div>
        <div class="text-gray-600 dark:text-gray-300" style="font-size: 14px; font-weight: 500; line-height: 1.25rem;">
            {{ $label }}
        </div>
    </div>
</div>
