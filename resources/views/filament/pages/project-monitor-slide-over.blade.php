@php
    use App\Services\ProjectMaterialReadiness;
    
    /** @var \App\Models\Project $project */
    $project = $record;
    $produced = $project->produced_qty ?? 0;
    $target = $project->target_qty ?? 0;
    $percent = $target > 0 ? min(100, round(($produced / $target) * 100, 1)) : 0;
    
    $readinessService = new ProjectMaterialReadiness();
    $readiness = $readinessService->getDetails($project);
    
    // Milestones by project status
    $statusOrder = ['planning', 'development', 'sampling', 'production', 'completed'];
    $currentStatusIndex = array_search($project->status, $statusOrder);
    if ($currentStatusIndex === false) {
        $currentStatusIndex = 0;
    }
    
    // Financials
    $costings = $project->costings;
    $materialCost = $costings->sum('material_cost') ?: ($costings->sum('estimated_total_cost') * 0.6);
    $laborCost = $costings->sum('labor_cost') ?: ($costings->sum('estimated_total_cost') * 0.4);
    $totalCost = $costings->sum('estimated_total_cost') ?: ($materialCost + $laborCost);
    $salesValue = $project->salesOrder?->total_amount ?? 0;
    $margin = $salesValue > 0 ? $salesValue - $totalCost : 0;
    $marginPercent = $salesValue > 0 ? round(($margin / $salesValue) * 100, 1) : 0;
@endphp

<div x-data="{ tab: 'timeline' }" style="display: flex; flex-direction: column; gap: 20px;">
    <!-- Header Card -->
    <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;" class="dark:bg-gray-800/60 dark:border-gray-700">
        <div>
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #6b7280; letter-spacing: 0.05em;">Project Operational Overview</div>
            <div style="font-size: 18px; font-weight: 800; color: #111827; margin-top: 2px;" class="dark:text-white">{{ $project->project_code }} — {{ $project->name }}</div>
            <div style="font-size: 12px; color: #4b5563; margin-top: 2px;" class="dark:text-gray-300">Customer: <strong>{{ $project->customer?->name ?? 'N/A' }}</strong></div>
        </div>
        <div>
            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
                {{ match($project->status) {
                    'production' => 'background-color: #fef3c7; color: #92400e;',
                    'completed' => 'background-color: #d1fae5; color: #065f46;',
                    'sampling' => 'background-color: #dbeafe; color: #1e40af;',
                    default => 'background-color: #f3f4f6; color: #374151;',
                } }}">
                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
            </span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div style="border-bottom: 2px solid #e5e7eb; display: flex; gap: 8px;" class="dark:border-gray-700">
        <button type="button" @click="tab = 'timeline'" 
            :style="tab === 'timeline' ? 'border-bottom: 2px solid #2563eb; color: #2563eb; font-weight: 700;' : 'color: #6b7280; font-weight: 500;'" 
            style="padding: 10px 16px; margin-bottom: -2px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer;">
            <svg style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Timeline Progress
        </button>

        <button type="button" @click="tab = 'resource'" 
            :style="tab === 'resource' ? 'border-bottom: 2px solid #2563eb; color: #2563eb; font-weight: 700;' : 'color: #6b7280; font-weight: 500;'" 
            style="padding: 10px 16px; margin-bottom: -2px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer;">
            <svg style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Resource & Materials
        </button>

        <button type="button" @click="tab = 'financial'" 
            :style="tab === 'financial' ? 'border-bottom: 2px solid #2563eb; color: #2563eb; font-weight: 700;' : 'color: #6b7280; font-weight: 500;'" 
            style="padding: 10px 16px; margin-bottom: -2px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer;">
            <svg style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Financial Summary
        </button>
    </div>

    <!-- TAB 1: TIMELINE -->
    <div x-show="tab === 'timeline'" style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Progress Bar -->
        <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px;" class="dark:bg-gray-800 dark:border-gray-700">
            <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #1f2937;" class="dark:text-gray-200">
                <span>Production Output</span>
                <span style="color: #2563eb;">{{ $percent }}% ({{ number_format($produced) }} / {{ number_format($target) }} pcs)</span>
            </div>
            <div style="width: 100%; background-color: #e5e7eb; border-radius: 9999px; height: 12px; overflow: hidden;" class="dark:bg-gray-700">
                <div style="background-color: #2563eb; height: 12px; border-radius: 9999px; width: {{ $percent }}%; transition: width 0.3s;"></div>
            </div>
        </div>

        <!-- Stage Checklist -->
        <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px;" class="dark:bg-gray-800 dark:border-gray-700">
            <h4 style="font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 16px;" class="dark:text-white">Milestone Progression</h4>
            <div style="display: flex; flex-direction: column; gap: 14px; position: relative; padding-left: 24px;">
                <div style="position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background-color: #e5e7eb;" class="dark:bg-gray-700"></div>

                @foreach (['Planning', 'Development', 'Sampling', 'Production', 'Completed'] as $idx => $stg)
                    @php $isPastOrCurrent = $idx <= $currentStatusIndex; @endphp
                    <div style="position: relative; display: flex; align-items: center; gap: 12px;">
                        <div style="position: absolute; left: -24px; width: 16px; height: 16px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800;
                            {{ $isPastOrCurrent ? 'background-color: #10b981; color: #ffffff;' : 'background-color: #ffffff; border: 2px solid #d1d5db; color: transparent;' }}">
                            ✓
                        </div>
                        <span style="font-size: 13px; {{ $isPastOrCurrent ? 'font-weight: 700; color: #111827;' : 'font-weight: 500; color: #9ca3af;' }}" class="dark:text-gray-200">
                            {{ $stg }}
                        </span>
                        @if (strtolower($stg) === $project->status)
                            <span style="padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; background-color: #dbeafe; color: #1d4ed8; text-transform: uppercase;">Current</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- TAB 2: RESOURCE & MATERIALS -->
    <div x-show="tab === 'resource'" style="display: flex; flex-direction: column; gap: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px;" class="dark:bg-gray-800 dark:border-gray-700">
            <div>
                <span style="font-size: 12px; color: #6b7280;">Material Readiness Status:</span>
                <span style="display: inline-block; margin-left: 8px; padding: 2px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;
                    {{ match($readiness['overall_status']) {
                        'Ready' => 'background-color: #d1fae5; color: #065f46;',
                        'Partial' => 'background-color: #fef3c7; color: #92400e;',
                        'At Risk' => 'background-color: #ffe4e6; color: #9f1239;',
                        default => 'background-color: #f3f4f6; color: #374151;',
                    } }}">
                    {{ $readiness['overall_status'] }}
                </span>
            </div>
            <div style="font-size: 12px; font-weight: 700; color: #374151;" class="dark:text-gray-300">
                Ready Items: <span style="color: #059669;">{{ $readiness['ready_items'] }} / {{ $readiness['total_items'] }}</span>
            </div>
        </div>

        @if (empty($readiness['items']))
            <div style="padding: 24px; text-align: center; font-size: 13px; color: #9ca3af; font-style: italic;">No active BOM items assigned or target quantity is zero.</div>
        @else
            <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;" class="dark:border-gray-700">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 12px;">
                    <thead style="background-color: #f3f4f6; color: #374151; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;" class="dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                        <tr>
                            <th style="padding: 10px 12px;">Material</th>
                            <th style="padding: 10px 12px;">Category</th>
                            <th style="padding: 10px 12px; text-align: right;">Needed</th>
                            <th style="padding: 10px 12px; text-align: right;">Available</th>
                            <th style="padding: 10px 12px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: #ffffff;" class="dark:bg-gray-900">
                        @foreach ($readiness['items'] as $item)
                            <tr style="border-bottom: 1px solid #f3f4f6;" class="dark:border-gray-800">
                                <td style="padding: 10px 12px; font-weight: 600; color: #111827;" class="dark:text-white">
                                    {{ $item['material_name'] }} <span style="color: #9ca3af; font-size: 11px;">({{ $item['material_code'] }})</span>
                                </td>
                                <td style="padding: 10px 12px; text-transform: capitalize; color: #4b5563;" class="dark:text-gray-300">{{ $item['category'] }}</td>
                                <td style="padding: 10px 12px; text-align: right; font-family: monospace; font-weight: 600;">{{ number_format($item['qty_needed'], 2) }} {{ $item['unit'] }}</td>
                                <td style="padding: 10px 12px; text-align: right; font-family: monospace; font-weight: 600;">{{ number_format($item['qty_available'], 2) }} {{ $item['unit'] }}</td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <span style="padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase;
                                        {{ match($item['status']) {
                                            'Ready' => 'background-color: #d1fae5; color: #065f46;',
                                            'Partial' => 'background-color: #fef3c7; color: #92400e;',
                                            default => 'background-color: #ffe4e6; color: #9f1239;',
                                        } }}">
                                        {{ $item['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- TAB 3: FINANCIAL SUMMARY -->
    <div x-show="tab === 'financial'" style="display: flex; flex-direction: column; gap: 16px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px;" class="dark:bg-gray-800 dark:border-gray-700">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600;">Material Cost (Est)</div>
                <div style="font-size: 15px; font-weight: 800; font-family: monospace; color: #111827; margin-top: 4px;" class="dark:text-white">Rp {{ number_format($materialCost, 0, ',', '.') }}</div>
            </div>
            <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px;" class="dark:bg-gray-800 dark:border-gray-700">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600;">Labor Cost (Est)</div>
                <div style="font-size: 15px; font-weight: 800; font-family: monospace; color: #111827; margin-top: 4px;" class="dark:text-white">Rp {{ number_format($laborCost, 0, ',', '.') }}</div>
            </div>
            <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px;" class="dark:bg-gray-800 dark:border-gray-700">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600;">Total Costing</div>
                <div style="font-size: 15px; font-weight: 800; font-family: monospace; color: #111827; margin-top: 4px;" class="dark:text-white">Rp {{ number_format($totalCost, 0, ',', '.') }}</div>
            </div>
            <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 16px;" class="dark:bg-gray-800 dark:border-gray-700">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600;">Sales Order Value</div>
                <div style="font-size: 15px; font-weight: 800; font-family: monospace; color: #059669; margin-top: 4px;">Rp {{ number_format($salesValue, 0, ',', '.') }}</div>
            </div>
        </div>

        <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center;" class="dark:bg-emerald-950/40 dark:border-emerald-800">
            <div>
                <div style="font-size: 11px; font-weight: 800; color: #065f46; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-emerald-300">Estimated Gross Margin</div>
                <div style="font-size: 12px; color: #047857;" class="dark:text-emerald-400">Sales Value minus Total Cost</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 20px; font-weight: 900; font-family: monospace; color: #047857;" class="dark:text-emerald-300">Rp {{ number_format($margin, 0, ',', '.') }}</div>
                <div style="font-size: 12px; font-weight: 800; color: #059669;" class="dark:text-emerald-400">({{ $marginPercent }}%)</div>
            </div>
        </div>
    </div>
</div>
