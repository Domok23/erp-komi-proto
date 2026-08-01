<x-filament-panels::page>
    <div class="top-mgmt-dashboard-container" style="display: flex; flex-direction: column; gap: 24px; font-family: system-ui, sans-serif;">
        <!-- Header Sub-Bar with Status Indicator -->
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 16px 24px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);" class="dark:bg-gray-800 dark:border-gray-700">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="position: relative; display: flex; height: 10px; width: 10px;">
                    <span style="position: absolute; display: inline-flex; height: 100%; width: 100%; border-radius: 9999px; background-color: #10b981; opacity: 0.75;" class="animate-pulse"></span>
                    <span style="position: relative; display: inline-flex; border-radius: 9999px; height: 10px; width: 10px; background-color: #10b981;"></span>
                </span>
                <span style="font-size: 14px; font-weight: 700; color: #111827;" class="dark:text-white">Top Management Executive Control</span>
            </div>
            
            <div style="display: flex; align-items: center; gap: 16px; font-size: 12px; color: #6b7280;" class="dark:text-gray-400">
                <span>System status: <strong style="color: #059669;" class="dark:text-emerald-400">Operational</strong></span>
                <span style="width: 1px; height: 16px; background-color: #e5e7eb;" class="dark:bg-gray-700"></span>
                <span>As of: <strong style="color: #111827;" class="dark:text-white">{{ now()->format('d M Y H:i:s') }}</strong></span>
            </div>
        </div>

        <!-- Row 1: Finance Chart (Revenue vs Cost) + Material Reservations Breakdown -->
        <div class="dashboard-row-two-col">
            <div class="chart-container-large">
                @livewire(\App\Filament\Widgets\ManagementFinanceChart::class)
            </div>
            <div class="chart-container-small">
                @livewire(\App\Filament\Widgets\MaterialReservationChart::class)
            </div>
        </div>

        <!-- Row 2: Sales Order Status Chart + Production Capacity Widget + Top High-Value Sales -->
        <div class="dashboard-row-three-col">
            <div class="widget-box">
                @livewire(\App\Filament\Widgets\SalesOrderStatusChart::class)
            </div>
            <div class="widget-box">
                @livewire(\App\Filament\Widgets\ProductionCapacityWidget::class)
            </div>
            <div class="widget-box">
                <x-filament::section class="h-full">
                    <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; gap: 16px;">
                        <div>
                            <h3 style="font-size: 14px; font-weight: 700; color: #111827; margin: 0;" class="dark:text-white">Top High-Value Sales</h3>
                            <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0 0;" class="dark:text-gray-400">Highest value orders in production</p>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px; margin: 12px 0;">
                            @forelse($this->getHighValueOrders() as $order)
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background-color: #f9fafb; border-radius: 8px; border: 1px solid #f3f4f6;" class="dark:bg-gray-700/50 dark:border-gray-700">
                                    <div style="display: flex; flex-direction: column; gap: 2px; min-width: 0;">
                                        <span style="font-size: 12px; font-weight: 700; color: #111827; font-family: monospace;" class="dark:text-white">{{ $order->so_number }}</span>
                                        <span style="font-size: 11px; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="dark:text-gray-400">{{ $order->customer->name ?? 'N/A' }}</span>
                                    </div>
                                    <span style="font-size: 11px; font-weight: 700; background-color: rgba(16, 185, 129, 0.1); color: #059669; padding: 4px 8px; border-radius: 9999px;" class="dark:bg-emerald-500/20 dark:text-emerald-400">
                                        IDR {{ number_format($order->grand_total, 0) }}
                                    </span>
                                </div>
                            @empty
                                <div style="text-align: center; color: #6b7280; font-size: 12px; padding: 20px 0;" class="dark:text-gray-400">
                                    No sales orders found.
                                </div>
                            @endforelse
                        </div>

                        <div style="border-top: 1px solid #f3f4f6; padding-top: 12px; font-size: 11px; color: #6b7280; text-align: right;" class="dark:border-gray-800 dark:text-gray-400">
                            <a href="/admin/sales-orders" style="color: #3b82f6; text-decoration: none; font-weight: 600;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">View All Orders →</a>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Section: Pending E-Sign Approvals -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0;" class="dark:text-white">
                    Awaiting E-Sign Authorization (Purchase Orders)
                </h3>
                <p style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0;" class="dark:text-gray-400">
                    Authorize pending procurement items directly from this dashboard.
                </p>
            </div>

            <div class="table-container" style="background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); padding: 20px;" class="dark:bg-gray-800 dark:border-gray-700">
                {{ $this->table }}
            </div>
        </div>
    </div>

    <!-- Custom CSS Styles -->
    <style>
        /* Row 1 layout: 2 columns */
        .dashboard-row-two-col {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            width: 100%;
        }
        
        /* Row 2 layout: 3 columns */
        .dashboard-row-three-col {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            width: 100%;
        }
        
        .chart-container-large {
            min-width: 0;
        }
        
        .chart-container-small {
            min-width: 0;
        }
        
        .widget-box {
            min-width: 0;
        }
        
        /* Restrict sizes of any SVG icon inside table empty state or elsewhere to prevent giant icon bug */
        .table-container svg, 
        .fi-ta-empty-state svg, 
        .fi-icon-btn svg,
        .fi-ta-actions svg {
            max-width: 48px !important;
            max-height: 48px !important;
        }
        .fi-ta-empty-state-icon {
            width: 48px !important;
            height: 48px !important;
            margin: 0 auto 12px auto !important;
        }
        
        @media (max-width: 1024px) {
            .dashboard-row-two-col, 
            .dashboard-row-three-col {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</x-filament-panels::page>
