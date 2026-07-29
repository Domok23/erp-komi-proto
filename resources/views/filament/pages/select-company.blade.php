<x-filament-panels::page.simple>
    <style>
        .company-card-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 16px 20px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-align: left;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .company-card-btn {
            background-color: #0f172a;
            border-color: #1e293b;
            box-shadow: none;
        }
        .company-card-btn:hover {
            border-color: #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
            transform: translateY(-1px);
        }
        .dark .company-card-btn:hover {
            border-color: #f59e0b;
        }
        .company-icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background-color: rgba(245, 158, 11, 0.1);
            color: #d97706;
            flex-shrink: 0;
        }
        .dark .company-icon-box {
            background-color: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        .company-icon-svg {
            width: 24px;
            height: 24px;
            display: block;
        }
        .company-arrow-svg {
            width: 20px;
            height: 20px;
            display: block;
            color: #94a3b8;
            transition: transform 0.2s ease;
        }
        .company-card-btn:hover .company-arrow-svg {
            color: #f59e0b;
            transform: translateX(3px);
        }
        .company-badge-main {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: #fef3c7;
            color: #92400e;
        }
        .dark .company-badge-main {
            background-color: rgba(245, 158, 11, 0.2);
            color: #fde68a;
        }
        .company-badge-branch {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: #dbeafe;
            color: #1e40af;
        }
        .dark .company-badge-branch {
            background-color: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
        @foreach ($companies as $company)
            <button
                type="button"
                wire:click="selectCompany({{ $company->id }})"
                class="company-card-btn"
            >
                <!-- Left Section: Icon and Texts -->
                <div style="display: flex; align-items: center; gap: 16px;">
                    <!-- Icon Container -->
                    <div class="company-icon-box">
                        <svg class="company-icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <!-- Text Info -->
                    <div>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700;" class="text-gray-900 dark:text-white">
                            {{ $company->name }}
                        </h3>
                        <div style="margin-top: 4px; display: flex; align-items: center; gap: 8px; font-size: 12px;" class="text-gray-500 dark:text-gray-400">
                            <span>Code: <strong style="font-family: monospace;" class="text-gray-700 dark:text-gray-300">{{ $company->code }}</strong></span>
                            <span>&bull;</span>
                            <span class="{{ $company->type === 'main' ? 'company-badge-main' : 'company-badge-branch' }}">
                                {{ $company->type === 'main' ? 'Main (Production)' : 'Branch (Warehouse)' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Section: Arrow Icon -->
                <div style="flex-shrink: 0; display: flex; align-items: center;">
                    <svg class="company-arrow-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </button>
        @endforeach
    </div>
</x-filament-panels::page.simple>

