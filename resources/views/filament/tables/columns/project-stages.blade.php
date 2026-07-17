@php
    $record = $getRecord();
    
    // Group job orders by task_type across all production orders
    $jobOrders = $record->productionOrders->flatMap->jobOrders;
    
    $stages = [
        'cutting' => 'Cutting',
        'sewing' => 'Sewing',
        'finishing' => 'Finishing',
        'qc' => 'Quality Control',
        'packing' => 'Packing',
    ];
    
    $stageStatuses = [];
    foreach ($stages as $type => $label) {
        $stageOrders = $jobOrders->where('task_type', $type);
        if ($stageOrders->isEmpty()) {
            $stageStatuses[$type] = 'not_started';
        } else {
            if ($stageOrders->every(fn($jo) => $jo->status === 'completed')) {
                $stageStatuses[$type] = 'completed';
            } elseif ($stageOrders->contains(fn($jo) => $jo->status === 'in_progress')) {
                $stageStatuses[$type] = 'in_progress';
            } elseif ($stageOrders->contains(fn($jo) => $jo->status === 'cancelled') && $stageOrders->every(fn($jo) => in_array($jo->status, ['completed', 'cancelled']))) {
                $stageStatuses[$type] = 'cancelled';
            } else {
                $stageStatuses[$type] = 'pending';
            }
        }
    }
    
    // Determine the current display text and styling
    $currentActive = [];
    $currentPending = [];
    $allCompleted = true;
    $anyStarted = false;
    
    foreach ($stages as $type => $label) {
        $status = $stageStatuses[$type];
        if ($status !== 'not_started') {
            $anyStarted = true;
        }
        if ($status === 'in_progress') {
            $currentActive[] = $label;
        }
        if ($status === 'pending') {
            $currentPending[] = $label;
        }
        if ($status !== 'completed' && $status !== 'not_started') {
            $allCompleted = false;
        }
    }
    
    // Check if everything is actually completed (if there are job orders and all are completed)
    if ($anyStarted && $allCompleted && count($currentActive) === 0 && count($currentPending) === 0) {
        // Find the last completed stage
        $lastCompleted = 'Production';
        foreach (array_reverse(array_keys($stages)) as $type) {
            if ($stageStatuses[$type] === 'completed') {
                $lastCompleted = $stages[$type];
                break;
            }
        }
        $displayText = "Completed ({$lastCompleted})";
        $badgeClass = "bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400";
    } elseif (count($currentActive) > 0) {
        // If there are active stages running
        $displayText = implode(' & ', $currentActive) . ' In Progress';
        $badgeClass = "bg-amber-500/10 text-amber-600 border border-amber-500/30 dark:bg-amber-500/20 dark:text-amber-400 font-semibold animate-pulse";
    } elseif (count($currentPending) > 0) {
        // If no active stages, but some are pending/ready to start
        $displayText = 'Awaiting ' . $currentPending[0];
        $badgeClass = "bg-sky-500/10 text-sky-600 border border-sky-500/20 dark:bg-sky-500/20 dark:text-sky-400";
    } elseif ($anyStarted) {
        // If some are completed but no pending or active (e.g. next stages not started/assigned yet)
        $lastCompletedIndex = -1;
        $keys = array_keys($stages);
        foreach ($keys as $index => $type) {
            if ($stageStatuses[$type] === 'completed') {
                $lastCompletedIndex = $index;
            }
        }
        
        if ($lastCompletedIndex !== -1 && $lastCompletedIndex < count($keys) - 1) {
            $nextStage = $stages[$keys[$lastCompletedIndex + 1]];
            $displayText = "Completed {$stages[$keys[$lastCompletedIndex]]} | Next: {$nextStage} (Awaiting Setup)";
        } else {
            $displayText = "Completed";
        }
        $badgeClass = "bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700";
    } else {
        $displayText = "Not Started";
        $badgeClass = "bg-gray-50 text-gray-400 border border-dashed border-gray-200 dark:bg-gray-900/50 dark:text-gray-500 dark:border-gray-800";
    }
@endphp

<div class="py-1">
    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium tracking-wide {{ $badgeClass }}">
        {{ $displayText }}
    </span>
</div>
