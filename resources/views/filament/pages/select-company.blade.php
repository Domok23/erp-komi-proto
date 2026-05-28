<x-filament-panels::page.simple>
    <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 8px;">
        @foreach ($companies as $company)
            <button
                type="button"
                wire:click="selectCompany({{ $company->id }})"
                style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 20px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s ease-in-out; text-align: left; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);"
                onmouseover="this.style.borderColor='#f59e0b'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';"
                onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';"
            >
                <!-- Left Section: Icon and Texts -->
                <div style="display: flex; align-items: center; gap: 16px;">
                    <!-- Icon Container -->
                    <div style="display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 8px; background-color: rgba(245, 158, 11, 0.1); color: #d97706; flex-shrink: 0;">
                        <svg style="width: 24px; height: 24px;" fill="none; stroke: currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke="currentColor"/>
                        </svg>
                    </div>
                    <!-- Text Info -->
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                            {{ $company->name }}
                        </h3>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">
                            Code: <strong>{{ $company->code }}</strong> &bull; {{ $company->type === 'main' ? 'Main (Production)' : 'Branch (Warehouse)' }}
                        </p>
                    </div>
                </div>

                <!-- Right Section: Arrow Icon -->
                <div style="color: #94a3b8; flex-shrink: 0; display: flex; align-items: center;">
                    <svg style="width: 20px; height: 20px;" fill="none; stroke: currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" stroke="currentColor"/>
                    </svg>
                </div>
            </button>
        @endforeach
    </div>
</x-filament-panels::page.simple>
