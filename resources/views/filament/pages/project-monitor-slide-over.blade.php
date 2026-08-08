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

<style>
    .pm-card {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        color: #111827;
    }
    .dark .pm-card {
        background-color: #1f2937 !important;
        border-color: #374151 !important;
        color: #f9fafb !important;
    }

    .pm-tab-track {
        background-color: #f3f4f6;
        border: 1px solid #e5e7eb;
    }
    .dark .pm-tab-track {
        background-color: #111827 !important;
        border-color: #374151 !important;
    }

    .pm-tab-btn {
        color: #4b5563;
        background-color: transparent;
        border: 1px solid transparent;
        font-weight: 500;
        min-width: 140px;
        padding: 10px 14px;
        font-size: 13px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .dark .pm-tab-btn {
        color: #9ca3af !important;
    }

    .pm-tab-btn.is-active {
        background-color: #ffffff !important;
        color: #111827 !important;
        border-color: #d1d5db !important;
        font-weight: 700 !important;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    .dark .pm-tab-btn.is-active {
        background-color: #1f2937 !important;
        color: #ffffff !important;
        border-color: #4b5563 !important;
    }

    .pm-tab-icon {
        width: 18px;
        height: 18px;
        min-width: 18px;
        min-height: 18px;
        display: inline-block;
    }

    @media (max-width: 640px) {
        .pm-tab-icon {
            display: none !important;
        }
        .pm-tab-btn {
            min-width: 0 !important;
            padding: 8px 6px !important;
            font-size: 11px !important;
            text-align: center !important;
            gap: 0 !important;
        }
    }

    .pm-text-muted {
        color: #6b7280;
    }
    .dark .pm-text-muted {
        color: #9ca3af !important;
    }

    .pm-text-main {
        color: #111827;
    }
    .dark .pm-text-main {
        color: #ffffff !important;
    }

    .pm-table-head {
        background-color: #f9fafb;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
    }
    .dark .pm-table-head {
        background-color: #374151 !important;
        color: #f3f4f6 !important;
        border-color: #4b5563 !important;
    }

    .pm-table-row {
        border-bottom: 1px solid #f3f4f6;
    }
    .dark .pm-table-row {
        border-color: #374151 !important;
    }

    .pm-card-spacer {
        margin-bottom: 12px !important;
    }
</style>

<div x-data="{ tab: 'timeline' }" style="display: flex; flex-direction: column; gap: 12px; padding-bottom: 12px;">
    <!-- Header Card -->
    <div style="border-radius: 12px; padding: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;" class="pm-card">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-size: 11px; font-weight: 700; background-color: #e5e7eb; color: #374151;" class="dark:bg-gray-700 dark:text-gray-200">
                    {{ $project->project_code }}
                </span>
                <span style="font-size: 12px; font-weight: 500;" class="pm-text-muted">
                    {{ ucfirst($project->type ?? 'Standard') }} Project
                </span>
            </div>
            <div style="font-size: 18px; font-weight: 800;" class="pm-text-main">{{ $project->name }}</div>
            <div style="font-size: 12px; margin-top: 2px;" class="pm-text-muted">Customer: <strong class="pm-text-main">{{ $project->customer?->name ?? 'N/A' }}</strong></div>
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

    <!-- Navigation Tabs (Pill Segmented Control) -->
    <div style="padding: 5px; border-radius: 12px; display: flex; gap: 6px; overflow-x: auto; width: 100%;" class="pm-tab-track">
        <button type="button" @click="tab = 'timeline'" 
            :class="{ 'is-active': tab === 'timeline' }"
            style="flex: 1;"
            class="pm-tab-btn">
            <svg class="pm-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Timeline Progress</span>
        </button>

        <button type="button" @click="tab = 'resource'" 
            :class="{ 'is-active': tab === 'resource' }"
            style="flex: 1;"
            class="pm-tab-btn">
            <svg class="pm-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span>Materials & Stock</span>
        </button>

        <button type="button" @click="tab = 'financial'" 
            :class="{ 'is-active': tab === 'financial' }"
            style="flex: 1;"
            class="pm-tab-btn">
            <svg class="pm-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Financial Summary</span>
        </button>
    </div>

    <!-- TAB 1: TIMELINE -->
    <div x-show="tab === 'timeline'">
        <!-- Card 1: Progress Bar -->
        <div style="border-radius: 12px; padding: 16px;" class="pm-card pm-card-spacer">
            <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; margin-bottom: 10px;" class="pm-text-main">
                <span>Production Output</span>
                <span style="color: #10b981; font-family: monospace; font-weight: 700;">{{ $percent }}% ({{ number_format($produced) }} / {{ number_format($target) }} pcs)</span>
            </div>
            <div style="width: 100%; border-radius: 9999px; height: 10px; overflow: hidden; background-color: #e5e7eb;" class="dark:bg-gray-700">
                <div style="background-color: #10b981; height: 10px; border-radius: 9999px; width: {{ $percent }}%; transition: width 0.3s;"></div>
            </div>
        </div>

        @if ($project->subProjects->count() > 0)
        <!-- Card 1b: Sub-Projects Progress Breakdown -->
        <div style="border-radius: 12px; padding: 16px;" class="pm-card pm-card-spacer">
            <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em;" class="pm-text-main">
                Sub-Projects Breakdown ({{ $project->subProjects->count() }})
            </h4>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($project->subProjects as $sp)
                    @php
                        $spTarget = $sp->target_qty ?? 0;
                        $spProduced = $sp->produced_qty ?? 0;
                        $spPct = $spTarget > 0 ? min(100, round(($spProduced / $spTarget) * 100, 1)) : 0;
                    @endphp
                    <div style="padding: 10px 12px; border-radius: 8px; border: 1px solid #e5e7eb;" class="dark:border-gray-700">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; margin-bottom: 6px;">
                            <div>
                                <span style="font-family: monospace; font-weight: 700; background-color: #f3f4f6; padding: 2px 6px; border-radius: 4px;" class="dark:bg-gray-700 pm-text-main">
                                    {{ $sp->code }}
                                </span>
                                <strong class="pm-text-main" style="margin-left: 6px;">{{ $sp->name }}</strong>
                                @if($sp->category)
                                    <span class="pm-text-muted" style="font-size: 11px;">({{ ucfirst($sp->category) }})</span>
                                @endif
                            </div>
                            <span style="font-family: monospace; font-weight: 700; color: #3b82f6;">
                                {{ $spPct }}% ({{ number_format($spProduced) }} / {{ number_format($spTarget) }} pcs)
                            </span>
                        </div>
                        <div style="width: 100%; border-radius: 9999px; height: 6px; overflow: hidden; background-color: #e5e7eb;" class="dark:bg-gray-700">
                            <div style="background-color: #3b82f6; height: 6px; border-radius: 9999px; width: {{ $spPct }}%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Card 2: Stage Checklist -->
        <div style="border-radius: 12px; padding: 16px;" class="pm-card">
            <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em;" class="pm-text-main">Milestone Progression</h4>
            <div style="display: flex; flex-direction: column; gap: 16px; position: relative; padding-left: 24px;">
                <div style="position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background-color: #e5e7eb;" class="dark:bg-gray-700"></div>

                @foreach (['Planning', 'Development', 'Sampling', 'Production', 'Completed'] as $idx => $stg)
                    @php $isPastOrCurrent = $idx <= $currentStatusIndex; @endphp
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; position: relative;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="position: absolute; left: -24px; width: 16px; height: 16px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;
                                {{ $isPastOrCurrent ? 'background-color: #10b981; color: #ffffff;' : 'background-color: #ffffff; border: 2px solid #d1d5db; color: transparent;' }}">
                                ✓
                            </div>
                            <span style="font-size: 13px; {{ $isPastOrCurrent ? 'font-weight: 700;' : 'font-weight: 500;' }}" class="{{ $isPastOrCurrent ? 'pm-text-main' : 'pm-text-muted' }}">
                                {{ $stg }}
                            </span>
                        </div>
                        @if (strtolower($stg) === $project->status)
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; text-transform: uppercase; background-color: #fef3c7; color: #92400e;">
                                Active Phase
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- TAB 2: RESOURCE & MATERIALS -->
    <div x-show="tab === 'resource'" style="display: none;">
        <!-- Card 1: Readiness Summary -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-radius: 12px;" class="pm-card pm-card-spacer">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                <span class="pm-text-muted" style="font-weight: 500;">Material Readiness:</span>
                <span style="display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;
                    {{ match($readiness['overall_status']) {
                        'Ready' => 'background-color: #d1fae5; color: #065f46;',
                        'Partial' => 'background-color: #fef3c7; color: #92400e;',
                        'At Risk' => 'background-color: #ffe4e6; color: #9f1239;',
                        default => 'background-color: #f3f4f6; color: #374151;',
                    } }}">
                    {{ $readiness['overall_status'] }}
                </span>
            </div>
            <div style="font-size: 13px; font-weight: 600;" class="pm-text-main">
                Ready Items: <strong style="color: #059669;" class="dark:text-emerald-400">{{ $readiness['ready_items'] }} / {{ $readiness['total_items'] }}</strong>
            </div>
        </div>

        <!-- Card 2: Materials Table -->
        @if (empty($readiness['items']))
            <div style="padding: 24px; text-align: center; font-size: 13px; font-style: italic; border-radius: 12px;" class="pm-card pm-text-muted">
                No active BOM items assigned or target quantity is zero.
            </div>
        @else
            <div style="border-radius: 12px; overflow-x: auto;" class="pm-card">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 12px;">
                    <thead class="pm-table-head">
                        <tr>
                            <th style="padding: 10px 14px;">Material</th>
                            <th style="padding: 10px 14px;">Category</th>
                            <th style="padding: 10px 14px; text-align: right;">Needed</th>
                            <th style="padding: 10px 14px; text-align: right;">Available</th>
                            <th style="padding: 10px 14px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($readiness['items'] as $item)
                            <tr class="pm-table-row">
                                <td style="padding: 10px 14px; font-weight: 600;" class="pm-text-main">
                                    {{ $item['material_name'] }}
                                    <span style="display: block; font-size: 11px; font-weight: 400; font-family: monospace;" class="pm-text-muted">{{ $item['material_code'] }}</span>
                                </td>
                                <td style="padding: 10px 14px; text-transform: capitalize;" class="pm-text-muted">{{ $item['category'] }}</td>
                                <td style="padding: 10px 14px; text-align: right; font-family: monospace; font-weight: 600;" class="pm-text-main">{{ number_format($item['qty_needed'], 2) }} {{ $item['unit'] }}</td>
                                <td style="padding: 10px 14px; text-align: right; font-family: monospace; font-weight: 600;" class="pm-text-main">{{ number_format($item['qty_available'], 2) }} {{ $item['unit'] }}</td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; text-transform: uppercase;
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
    <div x-show="tab === 'financial'" style="display: none;">
        <!-- Card 1: 4 Financial Metrics -->
        <div style="display: flex; flex-wrap: wrap; gap: 12px;" class="pm-card-spacer">
            <div style="flex: 1; min-width: 140px; padding: 14px; border-radius: 12px;" class="pm-card">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase;" class="pm-text-muted">Material Cost (Est)</div>
                <div style="font-size: 15px; font-weight: 800; font-family: monospace; margin-top: 4px;" class="pm-text-main">Rp {{ number_format($materialCost, 0, ',', '.') }}</div>
            </div>
            <div style="flex: 1; min-width: 140px; padding: 14px; border-radius: 12px;" class="pm-card">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase;" class="pm-text-muted">Labor Cost (Est)</div>
                <div style="font-size: 15px; font-weight: 800; font-family: monospace; margin-top: 4px;" class="pm-text-main">Rp {{ number_format($laborCost, 0, ',', '.') }}</div>
            </div>
            <div style="flex: 1; min-width: 140px; padding: 14px; border-radius: 12px;" class="pm-card">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase;" class="pm-text-muted">Total Costing</div>
                <div style="font-size: 15px; font-weight: 800; font-family: monospace; margin-top: 4px;" class="pm-text-main">Rp {{ number_format($totalCost, 0, ',', '.') }}</div>
            </div>
            <div style="flex: 1; min-width: 140px; padding: 14px; border-radius: 12px;" class="pm-card">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase;" class="pm-text-muted">Sales Order Value</div>
                <div style="font-size: 15px; font-weight: 800; font-family: monospace; color: #059669; margin-top: 4px;" class="dark:text-emerald-400">Rp {{ number_format($salesValue, 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Card 2: Gross Margin Card -->
        <div style="padding: 16px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;" class="dark:bg-emerald-950/40 dark:border-emerald-800">
            <div>
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #065f46;" class="dark:text-emerald-300">Estimated Gross Margin</div>
                <div style="font-size: 12px; color: #047857; margin-top: 2px;" class="dark:text-emerald-400">Sales Value minus Total Cost</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 18px; font-weight: 800; font-family: monospace; color: #065f46;" class="dark:text-emerald-300">Rp {{ number_format($margin, 0, ',', '.') }}</div>
                <div style="font-size: 12px; font-weight: 700; color: #059669;" class="dark:text-emerald-400">({{ $marginPercent }}%)</div>
            </div>
        </div>
    </div>
</div>
