@php
    $supplierItems = $record->items->where('is_subcon', false)->groupBy('supplier_id');
    $subconItems = $record->items->where('is_subcon', true)->groupBy('subcon_id');

    // Identify skipped items
    $skippedSupplierItems = $record->items->where('is_subcon', false)->whereNull('supplier_id');
    $skippedSubconItems = $record->items->where('is_subcon', true)->whereNull('subcon_id');
    $hasSkipped = $skippedSupplierItems->isNotEmpty() || $skippedSubconItems->isNotEmpty();
    
    $totalPoCount = 0;
    foreach ($supplierItems as $supplierId => $items) {
        if ($supplierId) $totalPoCount++;
    }
    foreach ($subconItems as $subconId => $items) {
        if ($subconId) $totalPoCount++;
    }
@endphp

<div class="po-modal-container">
    <style>
        .po-modal-container {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1f2937;
            max-height: 65vh;
            overflow-y: auto;
            padding-right: 4px;
        }
        .dark .po-modal-container {
            color: #d1d5db;
        }
        .po-intro {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .dark .po-intro {
            background-color: #1f2937;
            border-color: #374151;
        }
        .po-section-title {
            font-size: 14px;
            font-weight: 700;
            margin-top: 24px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #111827;
        }
        .dark .po-section-title {
            color: #f9fafb;
        }
        .po-section-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
        }
        .po-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background-color: #ffffff;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        .dark .po-card {
            border-color: #374151;
            background-color: #1f2937;
            box-shadow: none;
        }
        .po-card-header {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dark .po-card-header {
            background-color: #111827;
            border-color: #374151;
        }
        .po-card-title {
            font-weight: 700;
            font-size: 13px;
            color: #111827;
            margin: 0;
        }
        .dark .po-card-title {
            color: #f9fafb;
        }
        .po-card-subtitle {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 2px 0;
        }
        .po-table-wrapper {
            overflow-x: auto;
            width: 100%;
        }
        .po-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 12px;
        }
        .po-table th {
            background-color: #fafafa;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
            padding: 10px 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .po-table th {
            background-color: #111827;
            color: #9ca3af;
            border-color: #374151;
        }
        .po-table td {
            padding: 10px 16px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            color: #374151;
        }
        .dark .po-table td {
            border-color: #374151;
            color: #d1d5db;
        }
        .po-table tbody tr:hover {
            background-color: #f9fafb;
        }
        .dark .po-table tbody tr:hover {
            background-color: #1c2533;
        }
        .material-info {
            display: flex;
            flex-direction: column;
        }
        .material-name {
            font-weight: 600;
            color: #111827;
        }
        .dark .material-name {
            color: #f9fafb;
        }
        .material-code {
            font-family: monospace;
            font-size: 10px;
            color: #6b7280;
        }
        .dark .material-code {
            color: #9ca3af;
        }
        .material-notes {
            font-size: 10px;
            font-style: italic;
            color: #8b5cf6;
            margin-top: 2px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.2;
        }
        .badge-draft {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .dark .badge-draft {
            background-color: rgba(245, 158, 11, 0.1);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.2);
        }
        .badge-shortage {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 2px 6px;
        }
        .dark .badge-shortage {
            background-color: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.2);
        }
        .badge-ok {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 2px 6px;
        }
        .dark .badge-ok {
            background-color: rgba(34, 197, 94, 0.1);
            color: #86efac;
            border-color: rgba(34, 197, 94, 0.2);
        }
        .stock-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
        .stock-shortage-text {
            font-size: 9px;
            font-weight: 700;
            color: #ef4444;
        }
        .po-total-section {
            background-color: #fafafa;
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
        }
        .dark .po-total-section {
            background-color: #111827;
            border-color: #374151;
        }
        .po-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            color: #4b5563;
        }
        .dark .po-total-row {
            color: #9ca3af;
        }
        .po-total-row-grand {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #d1d5db;
            font-weight: 700;
            font-size: 13px;
            color: #111827;
        }
        .dark .po-total-row-grand {
            border-color: #4b5563;
            color: #f9fafb;
        }
        .warning-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 14px 16px;
            margin-top: 24px;
            margin-bottom: 12px;
            color: #92400e;
        }
        .dark .warning-box {
            background-color: rgba(251, 191, 36, 0.05);
            border-color: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
        }
        .warning-title {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .warning-list {
            margin: 0;
            padding-left: 20px;
            font-size: 11px;
        }
        .warning-list li {
            margin-bottom: 4px;
        }
    </style>

    <div class="po-intro">
        Generating POs will create draft purchase orders based on finalized planning items.
        A total of <strong>{{ $totalPoCount }}</strong> Purchase Order(s) will be created.
    </div>

    {{-- Supplier POs --}}
    @if ($supplierItems->isNotEmpty() && $supplierItems->keys()->filter()->isNotEmpty())
        <div>
            <div class="po-section-title">
                <span class="po-section-icon" style="background-color: #dbeafe; color: #1e40af;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: block;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </span>
                Supplier Purchase Orders
            </div>

            @foreach ($supplierItems as $supplierId => $items)
                @if (!$supplierId) @continue @endif
                @php
                    $supplier = $items->first()->supplier;
                    $subtotal = 0;
                @endphp
                <div class="po-card">
                    <div class="po-card-header">
                        <div>
                            <div class="po-card-subtitle" style="color: #2563eb;">Supplier PO</div>
                            <h4 class="po-card-title">{{ $supplier?->name ?? 'Unknown Supplier' }}</h4>
                        </div>
                        <span class="badge badge-draft">Draft</span>
                    </div>

                    <div class="po-table-wrapper">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Material</th>
                                    <th style="width: 20%; text-align: center;">Stock Status</th>
                                    <th style="width: 15%; text-align: center;">Order Qty</th>
                                    <th style="width: 15%; text-align: right;">Unit Price</th>
                                    <th style="width: 15%; text-align: right;">Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    @php
                                        $material = $item->material;
                                        $stockVal = $stocks[$item->material_id] ?? 0;
                                        $shortage = max(0, floatval($item->planned_qty) - floatval($stockVal));
                                        $itemTotalPrice = $shortage * floatval($item->unit_price);
                                        $subtotal += $itemTotalPrice;
                                    @endphp
                                    <tr style="@if($shortage <= 0) opacity: 0.6; background-color: #fafafa; @endif">
                                        <td>
                                            <div class="material-info">
                                                <span class="material-name" style="@if($shortage <= 0) color: #6b7280; font-weight: normal; @endif">{{ $material?->name ?? 'Unknown' }}</span>
                                                <span class="material-code">{{ $material?->code ?? '' }}</span>
                                                @if ($item->notes)
                                                    <span class="material-notes">Note: {{ $item->notes }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="stock-label">
                                                @if ($shortage > 0)
                                                    <span class="badge badge-shortage">
                                                        {{ number_format($stockVal, 2) }} {{ $item->unit }}
                                                    </span>
                                                    <span class="stock-shortage-text">Shortage: {{ number_format($shortage, 2) }}</span>
                                                @else
                                                    <span class="badge badge-ok">
                                                        {{ number_format($stockVal, 2) }} {{ $item->unit }}
                                                    </span>
                                                    <span style="font-size: 9px; font-weight: 700; color: #166534; margin-top: 2px;">Fully Stocked</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td style="text-align: center; font-weight: 600; @if($shortage <= 0) color: #9ca3af; @endif">
                                            {{ number_format($shortage, 2) }} <span style="font-size: 10px; font-weight: 400; color: #9ca3af;">{{ $item->unit }}</span>
                                        </td>
                                        <td style="text-align: right; font-family: monospace; @if($shortage <= 0) color: #9ca3af; @endif">
                                            Rp {{ number_format($item->unit_price, 2, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 600; font-family: monospace; @if($shortage <= 0) color: #9ca3af; @endif">
                                            Rp {{ number_format($itemTotalPrice, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="po-total-section">
                        @php
                            $ppn = $subtotal * 0.11;
                            $grandTotal = $subtotal + $ppn;
                        @endphp
                        <div class="po-total-row">
                            <span>Subtotal:</span>
                            <span style="font-family: monospace;">Rp {{ number_format($subtotal, 2, ',', '.') }}</span>
                        </div>
                        <div class="po-total-row">
                            <span>PPN (11%):</span>
                            <span style="font-family: monospace;">Rp {{ number_format($ppn, 2, ',', '.') }}</span>
                        </div>
                        <div class="po-total-row-grand">
                            <span>Total PO:</span>
                            <span style="font-family: monospace; color: #2563eb;">Rp {{ number_format($grandTotal, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Subcon POs --}}
    @if ($subconItems->isNotEmpty() && $subconItems->keys()->filter()->isNotEmpty())
        <div>
            <div class="po-section-title">
                <span class="po-section-icon" style="background-color: #f3e8ff; color: #6b21a8;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: block;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
                Subcontractor Services
            </div>

            @foreach ($subconItems as $subconId => $items)
                @if (!$subconId) @continue @endif
                @php
                    $subcon = $items->first()->subcon;
                    $totalCost = 0;
                @endphp
                <div class="po-card">
                    <div class="po-card-header">
                        <div>
                            <div class="po-card-subtitle" style="color: #7c3aed;">Subcon PO</div>
                            <h4 class="po-card-title">{{ $subcon?->name ?? 'Unknown Subcontractor' }}</h4>
                        </div>
                        <span class="badge badge-draft">Draft</span>
                    </div>

                    <div class="po-table-wrapper">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Description</th>
                                    <th style="width: 15%; text-align: center;">Service Qty</th>
                                    <th style="width: 15%; text-align: right;">Unit Price</th>
                                    <th style="width: 20%; text-align: right;">Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    @php
                                        $totalCost += floatval($item->total_price);
                                    @endphp
                                    <tr>
                                        <td style="font-weight: 600; color: #111827;">
                                            {{ $item->notes ?? 'Subcon service' }}
                                        </td>
                                        <td style="text-align: center; font-weight: 600;">
                                            {{ number_format($item->planned_qty, 2) }} <span style="font-size: 10px; font-weight: 400; color: #6b7280;">{{ $item->unit ?? 'pcs' }}</span>
                                        </td>
                                        <td style="text-align: right; font-family: monospace;">
                                            Rp {{ number_format($item->unit_price, 2, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 600; font-family: monospace;">
                                            Rp {{ number_format($item->total_price, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="po-total-section">
                        <div class="po-total-row-grand">
                            <span>Total Cost:</span>
                            <span style="font-family: monospace; color: #7c3aed;">Rp {{ number_format($totalCost, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Warning: Missing Supplier/Subcon --}}
    @if ($hasSkipped)
        <div class="warning-box">
            <h4 class="warning-title">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: inline-block;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Skipped Items (No PO Will Be Generated)
            </h4>
            <div style="font-size: 12px; margin-bottom: 8px;">
                The following planning items do not have an assigned Supplier or Subcontractor. They will be skipped and no POs will be generated:
            </div>
            <ul class="warning-list">
                @foreach ($skippedSupplierItems as $item)
                    <li>
                        <strong>Material:</strong> {{ $item->material?->name ?? 'Unknown Material' }} (Code: {{ $item->material?->code ?? '-' }}) - Qty: {{ number_format($item->planned_qty, 2) }} {{ $item->unit }}
                    </li>
                @endforeach
                @foreach ($skippedSubconItems as $item)
                    <li>
                        <strong>Subcon Service:</strong> {{ $item->notes ?? 'Subcon service' }} - Qty: {{ number_format($item->planned_qty, 2) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
