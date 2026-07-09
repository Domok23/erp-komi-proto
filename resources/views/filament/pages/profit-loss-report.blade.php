<x-filament-panels::page>
    <div class="profit-loss-container">
        <style>
            .profit-loss-container {
                --primary-color: #3b82f6;
                --emerald-color: #10b981;
                --rose-color: #f43f5e;
                --card-bg-light: #ffffff;
                --card-bg-dark: #111827;
                --text-primary-light: #111827;
                --text-primary-dark: #f9fafb;
                --text-secondary-light: #6b7280;
                --text-secondary-dark: #9ca3af;
                --border-light: #e5e7eb;
                --border-dark: #1f2937;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }

            /* Grids */
            .pl-grid-2 {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            @media (min-width: 768px) {
                .pl-grid-2 {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            .pl-grid-4 {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            @media (min-width: 640px) {
                .pl-grid-4 {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (min-width: 1024px) {
                .pl-grid-4 {
                    grid-template-columns: repeat(4, 1fr);
                }
            }

            /* Stat Card styling matching Filament standard */
            .pl-stat-card {
                background-color: var(--card-bg-light);
                border: 1px solid var(--border-light);
                border-radius: 0.75rem;
                padding: 1.25rem 1.5rem;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            }
            .dark .pl-stat-card {
                background-color: var(--card-bg-dark);
                border-color: var(--border-dark);
                box-shadow: none;
            }

            .pl-stat-label {
                font-size: 0.8125rem;
                font-weight: 500;
                color: var(--text-secondary-light);
                margin-bottom: 0.25rem;
            }
            .dark .pl-stat-label {
                color: var(--text-secondary-dark);
            }

            .pl-stat-value {
                font-size: 1.625rem;
                font-weight: 700;
                letter-spacing: -0.01em;
                line-height: 1.2;
                color: var(--text-primary-light);
            }
            .dark .pl-stat-value {
                color: var(--text-primary-dark);
            }

            /* Section styling matching Filament standard */
            .pl-section {
                background-color: var(--card-bg-light);
                border: 1px solid var(--border-light);
                border-radius: 0.75rem;
                padding: 1.5rem;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            }
            .dark .pl-section {
                background-color: var(--card-bg-dark);
                border-color: var(--border-dark);
                box-shadow: none;
            }

            .pl-section-title {
                font-size: 1rem;
                font-weight: 600;
                color: var(--text-primary-light);
                margin-top: 0;
                margin-bottom: 1.25rem;
                border-bottom: 1px solid #f3f4f6;
                padding-bottom: 0.75rem;
            }
            .dark .pl-section-title {
                color: var(--text-primary-dark);
                border-bottom-color: #1f2937;
            }

            /* Form inputs styling matching Filament */
            .pl-date-input {
                width: 100%;
                padding: 0.5rem 0.75rem;
                border-radius: 0.375rem;
                border: 1px solid var(--border-light);
                background-color: #ffffff;
                color: var(--text-primary-light);
                font-size: 0.875rem;
                outline: none;
                transition: border-color 0.15s, box-shadow 0.15s;
                box-sizing: border-box;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            }
            .pl-date-input:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 1px var(--primary-color);
            }
            .dark .pl-date-input {
                border-color: var(--border-dark);
                background-color: #1f2937;
                color: var(--text-primary-dark);
            }
            .dark .pl-date-input:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 1px var(--primary-color);
            }

            /* Table styling matching Filament */
            .pl-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.875rem;
            }
            .pl-table th {
                text-align: left;
                font-weight: 600;
                color: var(--text-secondary-light);
                padding: 0.625rem 0.875rem;
                background-color: #f9fafb;
                text-transform: none;
                font-size: 0.75rem;
                border-bottom: 1px solid var(--border-light);
            }
            .dark .pl-table th {
                color: var(--text-secondary-dark);
                background-color: #182235;
                border-bottom-color: var(--border-dark);
            }

            .pl-table td {
                padding: 0.75rem 0.875rem;
                border-bottom: 1px solid #f3f4f6;
                color: var(--text-primary-light);
            }
            .dark .pl-table td {
                border-bottom-color: #1f2937;
                color: var(--text-primary-dark);
            }

            .pl-table tr:hover {
                background-color: #f9fafb;
            }
            .dark .pl-table tr:hover {
                background-color: rgba(255, 255, 255, 0.01);
            }
            
            .mb-6 {
                margin-bottom: 1.5rem;
            }
            .block {
                display: block;
            }
            .mb-2 {
                margin-bottom: 0.5rem;
            }
        </style>

        <!-- Filter Section -->
        <div class="pl-section mb-6">
            <h2 class="pl-section-title">Filter Report Period</h2>
            
            <div class="pl-grid-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Start Date</label>
                    <input 
                        type="date" 
                        value="{{ $startDate }}" 
                        wire:model.live="startDate"
                        class="pl-date-input"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">End Date</label>
                    <input 
                        type="date" 
                        value="{{ $endDate }}" 
                        wire:model.live="endDate"
                        class="pl-date-input"
                    />
                </div>
            </div>
        </div>

        <!-- Summary Cards Grid -->
        <div class="pl-grid-4 mb-6">
            <!-- Card Revenue -->
            <div class="pl-stat-card">
                <div class="pl-stat-label">Total Revenue</div>
                <div class="pl-stat-value">
                    {{ 'IDR ' . number_format($this->getRevenue(), 0, ',', '.') }}
                </div>
            </div>

            <!-- Card Expense -->
            <div class="pl-stat-card">
                <div class="pl-stat-label">Total Expense</div>
                <div class="pl-stat-value">
                    {{ 'IDR ' . number_format($this->getExpense(), 0, ',', '.') }}
                </div>
            </div>

            <!-- Card Gross Profit -->
            <div class="pl-stat-card">
                <div class="pl-stat-label">Gross Profit</div>
                <div class="pl-stat-value">
                    {{ 'IDR ' . number_format($this->getGrossProfit(), 0, ',', '.') }}
                </div>
            </div>

            <!-- Card Profit Margin -->
            <div class="pl-stat-card">
                <div class="pl-stat-label">Profit Margin</div>
                <div class="pl-stat-value">
                    {{ number_format($this->getProfitMargin(), 2) }}%
                </div>
            </div>
        </div>

        <!-- Breakdown Lists Grid -->
        <div class="pl-grid-2">
            <!-- Revenue Breakdown -->
            <div class="pl-section">
                <h2 class="pl-section-title">Revenue Breakdown</h2>

                @if(count($this->getRevenueBreakdown()) > 0)
                    <div class="overflow-x-auto">
                        <table class="pl-table">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th style="text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getRevenueBreakdown() as $item)
                                    <tr>
                                        <td style="font-weight: 500;">
                                            {{ $item['credit_account']['account_name'] ?? 'N/A' }}
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            {{ 'IDR ' . number_format($item['total'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align: center; color: var(--text-secondary-light); padding: 2rem 0;">No revenue data for selected period</div>
                @endif
            </div>

            <!-- Expense Breakdown -->
            <div class="pl-section">
                <h2 class="pl-section-title">Expense Breakdown</h2>

                @if(count($this->getExpenseBreakdown()) > 0)
                    <div class="overflow-x-auto">
                        <table class="pl-table">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th style="text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getExpenseBreakdown() as $item)
                                    <tr>
                                        <td style="font-weight: 500;">
                                            {{ $item['debit_account']['account_name'] ?? 'N/A' }}
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            {{ 'IDR ' . number_format($item['total'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align: center; color: var(--text-secondary-light); padding: 2rem 0;">No expense data for selected period</div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
