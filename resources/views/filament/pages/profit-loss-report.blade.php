<x-filament-panels::page>
    <style>
        .pl-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        .pl-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            position: relative;
        }
        .dark .pl-card {
            background-color: #0f172a;
            border-color: #1e293b;
            box-shadow: none;
        }
        .pl-card-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 16px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f172a;
        }
        .dark .pl-card-title {
            color: #f8fafc;
            border-bottom-color: #1e293b;
        }
        .pl-grid-2 {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 16px;
        }
        @media (min-width: 640px) {
            .pl-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .pl-grid-4 {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 16px;
        }
        @media (min-width: 640px) {
            .pl-grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .pl-grid-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .pl-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
            color: #64748b;
        }
        .dark .pl-label {
            color: #94a3b8;
        }
        .pl-input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #0f172a;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            box-sizing: border-box;
        }
        .dark .pl-input {
            border-color: #334155;
            background-color: #1e293b;
            color: #f8fafc;
        }
        .pl-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .pl-stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .pl-stat-icon svg {
            width: 16px;
            height: 16px;
            display: block;
        }
        .pl-stat-val {
            font-size: 20px;
            font-weight: 800;
            font-family: 'Fira Code', monospace;
            letter-spacing: -0.02em;
        }
        .pl-table-view {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        .pl-table-view th {
            padding: 10px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: #f8fafc;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .pl-table-view th {
            background-color: #1e293b;
            color: #94a3b8;
            border-color: #334155;
        }
        .pl-table-view td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .dark .pl-table-view td {
            border-color: #1e293b;
            color: #cbd5e1;
        }
        .pl-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            display: inline-block;
        }
    </style>

    <div class="pl-wrapper">
        <!-- Filter Section -->
        <div class="pl-card">
            <h2 class="pl-card-title">
                <svg style="width: 16px; height: 16px;" class="text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter Period Laporan
            </h2>
            
            <div class="pl-grid-2">
                <div>
                    <label class="pl-label">Start Date</label>
                    <input 
                        type="date" 
                        value="{{ $startDate }}" 
                        wire:model.live="startDate"
                        class="pl-input"
                    />
                </div>
                <div>
                    <label class="pl-label">End Date</label>
                    <input 
                        type="date" 
                        value="{{ $endDate }}" 
                        wire:model.live="endDate"
                        class="pl-input"
                    />
                </div>
            </div>
        </div>

        <!-- Summary Cards Grid -->
        <div class="pl-grid-4">
            <!-- Card Revenue -->
            <div class="pl-card">
                <div class="pl-stat-header">
                    <span class="pl-label" style="margin-bottom: 0;">Total Revenue</span>
                    <div class="pl-stat-icon" style="background-color: rgba(16, 185, 129, 0.1); color: #059669;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>
                <div class="pl-stat-val" style="color: #059669;">
                    {{ 'IDR ' . number_format($this->getRevenue(), 0, ',', '.') }}
                </div>
            </div>

            <!-- Card Expense -->
            <div class="pl-card">
                <div class="pl-stat-header">
                    <span class="pl-label" style="margin-bottom: 0;">Total Expense</span>
                    <div class="pl-stat-icon" style="background-color: rgba(244, 63, 94, 0.1); color: #e11d48;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    </div>
                </div>
                <div class="pl-stat-val" style="color: #e11d48;">
                    {{ 'IDR ' . number_format($this->getExpense(), 0, ',', '.') }}
                </div>
            </div>

            <!-- Card Gross Profit -->
            <div class="pl-card">
                <div class="pl-stat-header">
                    <span class="pl-label" style="margin-bottom: 0;">Gross Profit</span>
                    <div class="pl-stat-icon" style="background-color: rgba(245, 158, 11, 0.1); color: #d97706;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="pl-stat-val" style="color: #d97706;">
                    {{ 'IDR ' . number_format($this->getGrossProfit(), 0, ',', '.') }}
                </div>
            </div>

            <!-- Card Profit Margin -->
            <div class="pl-card">
                <div class="pl-stat-header">
                    <span class="pl-label" style="margin-bottom: 0;">Profit Margin</span>
                    <div class="pl-stat-icon" style="background-color: rgba(14, 165, 233, 0.1); color: #0284c7;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                </div>
                <div class="pl-stat-val" style="color: #0284c7;">
                    {{ number_format($this->getProfitMargin(), 2) }}%
                </div>
            </div>
        </div>

        <!-- Breakdown Lists Grid -->
        <div class="pl-grid-2">
            <!-- Revenue Breakdown -->
            <div class="pl-card">
                <h2 class="pl-card-title">
                    <span class="pl-badge-dot" style="background-color: #10b981;"></span>
                    Revenue Breakdown
                </h2>

                @if(count($this->getRevenueBreakdown()) > 0)
                    <div style="overflow-x: auto;">
                        <table class="pl-table-view">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th style="text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getRevenueBreakdown() as $item)
                                    <tr>
                                        <td style="font-weight: 600;">
                                            {{ $item['credit_account']['account_name'] ?? 'N/A' }}
                                        </td>
                                        <td style="text-align: right; font-weight: 700; font-family: monospace; color: #059669;">
                                            {{ 'IDR ' . number_format($item['total'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align: center; color: #94a3b8; padding: 24px 0; font-size: 13px;">
                        No revenue data for selected period
                    </div>
                @endif
            </div>

            <!-- Expense Breakdown -->
            <div class="pl-card">
                <h2 class="pl-card-title">
                    <span class="pl-badge-dot" style="background-color: #f43f5e;"></span>
                    Expense Breakdown
                </h2>

                @if(count($this->getExpenseBreakdown()) > 0)
                    <div style="overflow-x: auto;">
                        <table class="pl-table-view">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th style="text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getExpenseBreakdown() as $item)
                                    <tr>
                                        <td style="font-weight: 600;">
                                            {{ $item['debit_account']['account_name'] ?? 'N/A' }}
                                        </td>
                                        <td style="text-align: right; font-weight: 700; font-family: monospace; color: #e11d48;">
                                            {{ 'IDR ' . number_format($item['total'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align: center; color: #94a3b8; padding: 24px 0; font-size: 13px;">
                        No expense data for selected period
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>



