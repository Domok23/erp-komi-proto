<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px; font-family: inherit;">
        <!-- Header Sub-Bar with Status Indicator -->
        <div class="top-mgmt-subbar" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 16px 24px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="position: relative; display: flex; height: 10px; width: 10px;">
                    <span style="position: absolute; display: inline-flex; height: 100%; width: 100%; border-radius: 9999px; background-color: #10b981; opacity: 0.75;" class="animate-pulse"></span>
                    <span style="position: relative; display: inline-flex; border-radius: 9999px; height: 10px; width: 10px; background-color: #10b981;"></span>
                </span>
                <span class="subbar-title" style="font-size: 14px; font-weight: 700; color: #111827;">Top Management Executive Control</span>
            </div>
            
            <div class="subbar-text" style="display: flex; align-items: center; gap: 16px; font-size: 12px; color: #6b7280;">
                <span>System status: <strong style="color: #059669;">Operational</strong></span>
                <span class="subbar-divider" style="width: 1px; height: 16px; background-color: #e5e7eb;"></span>
                <span>As of: <strong class="subbar-time" style="color: #111827;">{{ now()->format('d M Y H:i:s') }}</strong></span>
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
                <x-filament::section
                    heading="Top High-Value Sales"
                    description="Highest value orders in production"
                    class="h-full"
                >
                    <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; gap: 16px;">

                        <div style="display: flex; flex-direction: column; justify-content: flex-start; flex-grow: 1; gap: 10px;">
                            @forelse($this->getHighValueOrders() as $order)
                                <div class="high-value-item" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background-color: #f9fafb; border-radius: 8px; border: 1px solid #f3f4f6;">
                                    <div style="display: flex; flex-direction: column; gap: 2px; min-width: 0;">
                                        <span class="so-number" style="font-size: 12px; font-weight: 700; color: #111827; font-family: monospace;">{{ $order->so_number }}</span>
                                        <span class="customer-name" style="font-size: 11px; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $order->customer->name ?? 'N/A' }}</span>
                                    </div>
                                    <span class="price-pill" style="font-size: 11px; font-weight: 700; background-color: rgba(16, 185, 129, 0.1); color: #059669; padding: 4px 8px; border-radius: 9999px;">
                                        IDR {{ number_format($order->grand_total, 0) }}
                                    </span>
                                </div>
                            @empty
                                <div class="card-subtext" style="text-align: center; color: #6b7280; font-size: 12px; padding: 20px 0;">
                                    No sales orders found.
                                </div>
                            @endforelse
                        </div>

                        <div class="card-footer" style="margin-top: auto; border-top: 1px solid #f3f4f6; padding-top: 12px; font-size: 11px; color: #6b7280; text-align: right;">
                            <a wire:navigate href="{{ \App\Filament\Resources\SalesOrderResource::getUrl('index') }}" style="color: #3b82f6; text-decoration: none; font-weight: 600;">View All Orders →</a>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Section: Pending E-Sign Approvals -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div>
                <h3 class="card-title" style="font-size: 16px; font-weight: 700; color: #111827; margin: 0;">
                    Awaiting E-Sign Authorization (Purchase Orders)
                </h3>
                <p class="card-subtext" style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0;">
                    Authorize pending procurement items directly from this dashboard.
                </p>
            </div>

            <div>
                {{ $this->table }}
            </div>
        </div>
    </div>

    <!-- Scoped CSS Styles with Dark Mode Support -->
    <style>
        .dashboard-row-two-col {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            width: 100%;
            align-items: stretch;
        }
        
        .dashboard-row-three-col {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            width: 100%;
            align-items: stretch;
        }
        
        .chart-container-large,
        .chart-container-small,
        .widget-box {
            min-width: 0;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chart-container-large > div,
        .chart-container-small > div,
        .widget-box > div {
            display: flex;
            flex-direction: column;
            flex: 1 1 0%;
            height: 100%;
        }

        .chart-container-large section,
        .chart-container-small section,
        .widget-box section,
        .widget-box .fi-section,
        .widget-box .fi-wi-chart,
        .widget-box .fi-section-content-ctn,
        .widget-box .fi-section-content {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 0% !important;
            height: 100% !important;
        }

        .widget-box .fi-section-content {
            justify-content: space-between !important;
        }

        /* Dark Mode Overrides */
        .dark .top-mgmt-subbar {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            box-shadow: none !important;
        }
        .dark .subbar-title,
        .dark .subbar-time,
        .dark .card-title,
        .dark .so-number {
            color: #f9fafb !important;
        }
        .dark .subbar-text,
        .dark .card-subtext,
        .dark .customer-name {
            color: #9ca3af !important;
        }
        .dark .subbar-divider {
            background-color: #374151 !important;
        }
        .dark .high-value-item {
            background-color: rgba(55, 65, 81, 0.5) !important;
            border-color: #374151 !important;
        }
        .dark .price-pill {
            background-color: rgba(16, 185, 129, 0.2) !important;
            color: #34d399 !important;
        }
        .dark .card-footer {
            border-top-color: #374151 !important;
        }
        
        @media (max-width: 1024px) {
            .dashboard-row-two-col, 
            .dashboard-row-three-col {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</x-filament-panels::page>
