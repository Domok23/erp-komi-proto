<div 
    class="material-picker-modal-wrap"
    x-data="{
        focusSearch() {
            setTimeout(() => {
                const searchInput = this.$el.querySelector('.fi-ta-search-field input, input[type=search], input[placeholder*=Search]');
                if (searchInput) {
                    searchInput.focus();
                }
            }, 100);
        }
    }"
    x-init="focusSearch()"
    x-on:open-modal.window="focusSearch()"
>
    <style>
        .material-picker-modal-wrap {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            display: flex;
            flex-direction: column;
        }
        .material-picker-modal-wrap .fi-ta,
        .material-picker-modal-wrap .fi-ta-ctn {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            display: flex !important;
            flex-direction: column !important;
        }
        .material-picker-modal-wrap .fi-ta-content {
            max-height: 52vh !important;
            overflow-y: auto !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .material-picker-modal-wrap .fi-ta-content {
            border-top-color: rgba(255, 255, 255, 0.08);
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        .material-picker-modal-wrap .fi-ta-table {
            width: 100% !important;
            min-width: 600px !important;
        }
        .material-picker-modal-wrap .fi-ta-filters-header {
            display: none !important;
        }
        .material-picker-modal-wrap .fi-ta-filters-heading {
            display: none !important;
        }
        .material-picker-modal-wrap .fi-ta-filters-actions-ctn {
            display: none !important;
        }
        .material-picker-modal-wrap .fi-ta-filters label,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field-wrp-label,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field-label-col,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field-label-ctn {
            display: none !important;
        }
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field-wrp,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-select-wrp {
            gap: 0 !important;
            row-gap: 0 !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        .material-picker-modal-wrap .fi-ta-filters {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        /* Scoped styling for Step 2 Allocation Matrix */
        .mat-alloc-container {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            width: 100%;
        }
        .mat-alloc-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
        }
        .dark .mat-alloc-banner {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.08);
        }
        .mat-alloc-banner-left {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }
        .mat-alloc-banner-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
            line-height: 1.25rem;
        }
        .dark .mat-alloc-banner-title {
            color: #f9fafb;
        }
        .mat-alloc-banner-sub {
            font-size: 0.75rem;
            color: #6b7280;
            margin: 0.25rem 0 0 0;
            line-height: 1rem;
        }
        .dark .mat-alloc-banner-sub {
            color: #9ca3af;
        }
        .mat-alloc-table-wrap {
            max-height: 52vh;
            overflow-y: auto;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            min-height: 200px;
            -webkit-overflow-scrolling: touch;
        }
        .dark .mat-alloc-table-wrap {
            border-color: rgba(255, 255, 255, 0.08);
            background: #18181b;
        }
        .mat-alloc-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }
        .mat-alloc-th {
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #374151;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .mat-alloc-th {
            color: #9ca3af;
            background: rgba(255, 255, 255, 0.03);
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        .mat-alloc-tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.15s ease;
        }
        .dark .mat-alloc-tr {
            border-bottom-color: rgba(255, 255, 255, 0.04);
        }
        .mat-alloc-tr:last-child {
            border-bottom: none;
        }
        .mat-alloc-tr:hover {
            background-color: #f9fafb;
        }
        .dark .mat-alloc-tr:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }
        .mat-alloc-td {
            padding: 0.75rem 1rem;
            vertical-align: top;
        }
        .mat-alloc-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 0;
            margin-top: 0;
        }
        .mat-alloc-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: #111827;
            line-height: 1.25rem;
        }
        .dark .mat-alloc-name {
            color: #f9fafb;
        }
        .mat-alloc-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.375rem;
            margin-top: 0.375rem;
            font-size: 0.75rem;
            color: #6b7280;
        }
        .dark .mat-alloc-meta {
            color: #9ca3af;
        }
        .mat-alloc-code-badge {
            display: inline-block;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-family: monospace;
            font-size: 0.75rem;
            font-weight: 600;
            background: #f3f4f6;
            color: #374151;
        }
        .dark .mat-alloc-code-badge {
            background: rgba(255, 255, 255, 0.08);
            color: #d1d5db;
        }
        .mat-alloc-waste-hint {
            font-size: 11px;
            color: #9ca3af;
            display: block;
            margin-top: 4px;
            line-height: 1rem;
        }

        /* Scoped Searchable Combobox for Component */
        .mat-combo-wrap {
            position: relative;
            width: 250px;
            max-width: 100%;
        }
        .mat-combo-input-box {
            display: flex;
            align-items: center;
            width: 100%;
            position: relative;
        }
        .mat-combo-chevron {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px !important;
            height: 14px !important;
            max-width: 14px !important;
            max-height: 14px !important;
            color: #9ca3af;
            pointer-events: none;
        }
        .mat-combo-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 100;
            max-height: 200px;
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 4px 0;
        }
        .dark .mat-combo-dropdown {
            background: #18181b;
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .mat-combo-item {
            padding: 7px 12px;
            font-size: 0.8125rem;
            color: #374151;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background-color 0.1s ease;
        }
        .dark .mat-combo-item {
            color: #e5e7eb;
        }
        .mat-combo-item:hover {
            background: #f3f4f6;
        }
        .dark .mat-combo-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .mat-combo-item.active {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 600;
        }
        .dark .mat-combo-item.active {
            background: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
        }
        .mat-combo-create {
            padding: 8px 12px;
            font-size: 0.8125rem;
            color: #2563eb;
            background: #eff6ff;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            border-top: 1px solid #e5e7eb;
            font-weight: 600;
        }
        .dark .mat-combo-create {
            color: #60a5fa;
            background: rgba(37, 99, 235, 0.1);
            border-top-color: rgba(255, 255, 255, 0.08);
        }
        .mat-combo-create:hover {
            background: #dbeafe;
        }
        .dark .mat-combo-create:hover {
            background: rgba(37, 99, 235, 0.2);
        }
        .mat-combo-empty {
            padding: 8px 12px;
            font-size: 0.75rem;
            color: #9ca3af;
            text-align: center;
        }

        /* Responsive Media Queries */
        @media (max-width: 768px) {
            .mat-alloc-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 0.875rem 1rem;
            }
            .mat-alloc-table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .mat-alloc-table {
                min-width: 680px;
            }
            .mat-alloc-th,
            .mat-alloc-td {
                padding: 0.5rem 0.75rem;
            }
            .mat-alloc-footer {
                flex-direction: column-reverse;
                align-items: stretch;
                gap: 0.75rem;
            }
            .mat-alloc-footer > div {
                width: 100%;
            }
            .mat-alloc-footer button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    @if ($step === 'picker')
        <div wire:key="picker-table-{{ implode('-', $selectedTableRecords) }}">
            {{ $this->table }}
        </div>
    @elseif ($step === 'allocation')
        <div class="mat-alloc-container" wire:key="allocation-form-{{ count($selectedMaterials) }}">
            <!-- Header Summary (Step 2 badge removed) -->
            <div class="mat-alloc-banner">
                    <div class="mat-alloc-banner-left">
                        <div>
                            <h4 class="mat-alloc-banner-title">
                                Allocate Components & Actual Consumption
                            </h4>
                            <p class="mat-alloc-banner-sub">
                                Specify the bag component placement, net actual consumption per unit, and optional notes for each selected material.
                            </p>
                        </div>
                    </div>
                    <div>
                        <x-filament::badge color="warning" icon="heroicon-m-cube">
                            {{ count($selectedMaterials) }} Material(s) Selected
                        </x-filament::badge>
                    </div>
                </div>

                <!-- Allocation Table -->
                <div class="mat-alloc-table-wrap">
                    <table class="mat-alloc-table">
                        <thead>
                            <tr>
                                <th class="mat-alloc-th" style="width: 32%; min-width: 180px;">Material Details</th>
                                <th class="mat-alloc-th" style="width: 250px; min-width: 250px; max-width: 250px;">Component Placement</th>
                                <th class="mat-alloc-th" style="width: 15%; min-width: 125px;">Actual Consumption</th>
                                <th class="mat-alloc-th" style="width: 24%; min-width: 150px;">Notes</th>
                                <th class="mat-alloc-th" style="width: 5%; min-width: 48px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selectedMaterials as $index => $item)
                                <tr class="mat-alloc-tr">
                                    <!-- Material Info -->
                                    <td class="mat-alloc-td">
                                        <div class="mat-alloc-name">
                                            {{ $item['name'] }}
                                        </div>
                                        <div class="mat-alloc-meta">
                                            <span class="mat-alloc-code-badge">
                                                {{ $item['code'] }}
                                            </span>
                                            @if (!empty($item['category']))
                                                <x-filament::badge size="xs" color="gray">
                                                    {{ $item['category'] }}
                                                </x-filament::badge>
                                            @endif
                                            @if (!empty($item['color']) || !empty($item['size']))
                                                <span>
                                                    {{ implode(' • ', array_filter([$item['color'] ?? null, $item['size'] ?? null])) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Component Placement: Searchable Select Combobox with Quick-Add -->
                                    <td class="mat-alloc-td" style="width: 250px; min-width: 250px; max-width: 250px;">
                                        <div 
                                            x-data="{
                                                open: false,
                                                search: @entangle('selectedMaterials.' . $index . '.component'),
                                                options: @js(array_values($availableComponents)),
                                                get filtered() {
                                                    if (!this.search || !this.search.trim()) return this.options;
                                                    const q = this.search.toLowerCase().trim();
                                                    return this.options.filter(o => o.toLowerCase().includes(q));
                                                },
                                                select(val) {
                                                    this.search = val;
                                                    this.open = false;
                                                },
                                                create() {
                                                    const val = (this.search || '').trim();
                                                    if (!val) return;
                                                    $wire.createNewComponent(val, {{ $index }}).then(() => {
                                                        if (!this.options.includes(val)) {
                                                            this.options.push(val);
                                                            this.options.sort();
                                                        }
                                                        this.search = val;
                                                        this.open = false;
                                                    });
                                                }
                                            }"
                                            class="mat-combo-wrap"
                                            @click.away="open = false"
                                        >
                                            <x-filament::input.wrapper>
                                                <div class="mat-combo-input-box">
                                                    <x-filament::input
                                                        type="text"
                                                        x-model="search"
                                                        @focus="open = true"
                                                        @input="open = true"
                                                        @keydown.enter.prevent="if (filtered.length > 0) select(filtered[0]); else create();"
                                                        placeholder="Search or add component..."
                                                        autocomplete="off"
                                                        style="padding-right: 28px;"
                                                    />
                                                    <svg class="mat-combo-chevron" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </div>
                                            </x-filament::input.wrapper>

                                            <div x-show="open" class="mat-combo-dropdown" style="display: none;">
                                                <template x-for="opt in filtered" :key="opt">
                                                    <div 
                                                        @click="select(opt)"
                                                        class="mat-combo-item"
                                                        :class="{ 'active': search === opt }"
                                                    >
                                                        <span x-text="opt"></span>
                                                        <span x-show="search === opt" style="font-weight: bold;">✓</span>
                                                    </div>
                                                </template>

                                                <div 
                                                    x-show="search && search.trim() && !options.some(o => o.toLowerCase() === search.toLowerCase().trim())"
                                                    @click="create()"
                                                    class="mat-combo-create"
                                                >
                                                    <span style="font-size: 14px; font-weight: bold;">+</span>
                                                    <span>Create "<strong x-text="search ? search.trim() : ''"></strong>"</span>
                                                </div>

                                                <div x-show="options.length === 0 && (!search || !search.trim())" class="mat-combo-empty">
                                                    No components found. Type to create.
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Actual Consumption (2 Decimal Places) -->
                                    <td class="mat-alloc-td">
                                        <x-filament::input.wrapper :suffix="$item['uom'] ?? 'pcs'">
                                            <x-filament::input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                placeholder="0.00"
                                                wire:model.defer="selectedMaterials.{{ $index }}.actual_consumption"
                                                required
                                                style="text-align: right; font-weight: 600;"
                                            />
                                        </x-filament::input.wrapper>
                                        <span class="mat-alloc-waste-hint">
                                            Yield 3% waste
                                        </span>
                                    </td>

                                    <!-- Notes -->
                                    <td class="mat-alloc-td">
                                        <x-filament::input.wrapper>
                                            <x-filament::input
                                                type="text"
                                                wire:model.defer="selectedMaterials.{{ $index }}.notes"
                                                placeholder="Optional notes..."
                                            />
                                        </x-filament::input.wrapper>
                                    </td>

                                    <!-- Remove Action (Centered) -->
                                    <td class="mat-alloc-td" style="text-align: center; vertical-align: top;">
                                        <div style="display: flex; align-items: center; justify-content: center; height: 36px; margin: 0 auto;">
                                            <x-filament::icon-button
                                                icon="heroicon-m-trash"
                                                color="danger"
                                                size="sm"
                                                wire:click="removeSelectedMaterial({{ $index }})"
                                                tooltip="Remove"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer Action Bar -->
                <div class="mat-alloc-footer">
                    <div>
                        <x-filament::button 
                            color="gray" 
                            icon="heroicon-m-arrow-left" 
                            wire:click="backToPicker"
                        >
                            Back to Material Catalog
                        </x-filament::button>
                    </div>

                    <div>
                        <x-filament::button 
                            color="primary" 
                            icon="heroicon-m-check" 
                            wire:click="saveAllocation"
                            wire:loading.attr="disabled"
                        >
                            Save All to R&D
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
