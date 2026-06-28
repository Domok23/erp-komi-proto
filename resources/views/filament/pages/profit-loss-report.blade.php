<x-filament-panels::page>
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Profit/Loss Report</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" 
                       value="{{ $startDate }}" 
                       wire:model.live="startDate"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" 
                       value="{{ $endDate }}" 
                       wire:model.live="endDate"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Summary</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Total Revenue</div>
                <div class="text-2xl font-bold text-green-600">
                    {{ 'IDR ' . number_format($this->getRevenue(), 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Total Expense</div>
                <div class="text-2xl font-bold text-red-600">
                    {{ 'IDR ' . number_format($this->getExpense(), 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Gross Profit</div>
                <div class="text-2xl font-bold {{ $this->getGrossProfit() >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                    {{ 'IDR ' . number_format($this->getGrossProfit(), 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-500">Profit Margin</div>
                <div class="text-2xl font-bold {{ $this->getProfitMargin() >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($this->getProfitMargin(), 2) }}%
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Revenue Breakdown</h2>

            @if(count($this->getRevenueBreakdown()) > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($this->getRevenueBreakdown() as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $item['credit_account']['account_name'] ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-right text-green-600">
                                    {{ 'IDR ' . number_format($item['total'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-gray-500 text-center py-4">No revenue data for selected period</div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Expense Breakdown</h2>

            @if(count($this->getExpenseBreakdown()) > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($this->getExpenseBreakdown() as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $item['debit_account']['account_name'] ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-right text-red-600">
                                    {{ 'IDR ' . number_format($item['total'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-gray-500 text-center py-4">No expense data for selected period</div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
