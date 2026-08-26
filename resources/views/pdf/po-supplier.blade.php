<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $po->po_number }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10pt; line-height: 1.4; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; color: #495057; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; }
        .company-name { font-size: 18pt; font-weight: bold; color: #212529; margin: 0; }
        .company-info { font-size: 9pt; color: #6c757d; margin-top: 5px; }
        .title-bar { text-align: center; margin: 20px 0; }
        .title { font-size: 16pt; font-weight: bold; letter-spacing: 1px; margin: 0; color: #212529; }
        .metadata-container { margin-bottom: 20px; }
        .left-col { float: left; width: 50%; }
        .right-col { float: right; width: 45%; }
        .clear { clear: both; }
        .section-title { font-size: 12pt; font-weight: bold; color: #212529; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; margin-top: 25px; margin-bottom: 10px; }
        .totals-table { width: 40%; float: right; margin-top: 15px; }
        .totals-table td { border-bottom: none; padding: 4px 8px; }
        .totals-table tr.grand-total td { font-weight: bold; font-size: 11pt; border-top: 1px solid #dee2e6; border-bottom: 2px double #dee2e6; padding-top: 6px; }
        .signature-block { margin-top: 50px; }
        .signature-col-right { width: 45%; text-align: center; float: right; }
        .signature-col-left { width: 45%; text-align: center; float: left; }
        .signature-img { max-width: 180px; max-height: 70px; margin: 10px 0; border-bottom: 1px solid #ddd; }
        .signature-placeholder { height: 70px; margin: 10px 0; }
        .footer { margin-top: 50px; font-size: 8pt; color: #868e96; text-align: center; border-top: 1px solid #dee2e6; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'PT KOMITRANDO EMPORIO' }}</div>
        <div class="company-info">
            {{ $company->address ?? 'Jalan Solo KM 14' }}, {{ $company->city ?? 'Sleman, Yogyakarta' }}<br>
            Phone: {{ $company->phone ?? '-' }} | Email: {{ $company->email ?? '-' }} | NPWP: {{ $company->npwp ?? '-' }}
        </div>
    </div>

    <div class="title-bar">
        <div class="title">PURCHASE ORDER</div>
    </div>

    <div class="metadata-container">
        <div class="left-col">
            <strong>SUPPLIER INFO:</strong><br>
            {{ $supplier->name ?? '-' }}<br>
            {{ $supplier->address ?? '-' }}<br>
            {{ $supplier->city ?? '-' }}<br>
            Phone: {{ $supplier->phone ?? '-' }} | NPWP: {{ $supplier->npwp ?? '-' }}
        </div>
        <div class="right-col">
            <strong>PO DETAILS:</strong><br>
            <table>
                <tr>
                    <td style="border: none; padding: 2px 0; width: 45%;">PO Number:</td>
                    <td style="border: none; padding: 2px 0;"><strong>{{ $po->po_number }}</strong></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">PO Date:</td>
                    <td style="border: none; padding: 2px 0;">{{ $po->po_date?->format('d M Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Delivery Date:</td>
                    <td style="border: none; padding: 2px 0;">{{ $po->delivery_date?->format('d M Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Project Reference:</td>
                    <td style="border: none; padding: 2px 0;">{{ $po->project_names }}</td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>
    </div>

    @php
        $groupedItems = $po->items->groupBy(fn($item) => ($item->material_id ? 'mat_' . $item->material_id : 'desc_' . ($item->description ?? '')) . '_' . ($item->component ?? ''))
            ->map(function($group) {
                $first = $group->first();
                $allocations = $group->map(function($i) {
                    $projName = $i->project?->name ?? $i->subProject?->project?->name;
                    if ($i->subProject) {
                        return $projName ? "[{$projName}] {$i->subProject->name}" : $i->subProject->name;
                    }
                    if ($projName) {
                        return "[{$projName}]";
                    }
                    return null;
                })->filter()->unique()->implode(', ');

                return (object) [
                    'material' => $first->material,
                    'component' => $first->component,
                    'description' => $first->description ?? $first->material?->name,
                    'qty' => $group->sum('qty'),
                    'unit' => $first->unit,
                    'unit_price' => $first->unit_price,
                    'total_price' => $group->sum('total_price'),
                    'allocation_names' => $allocations !== '' ? $allocations : '-',
                ];
            })->values();

        $hasAllocations = $groupedItems->contains(fn($item) => $item->allocation_names !== '-');
    @endphp

    <div class="section-title">ORDER ITEMS</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                @if($hasAllocations)
                <th style="width: 22%;">Project / Allocation</th>
                @endif
                <th>Material Code</th>
                <th>Description</th>
                <th class="text-right" style="width: 12%;">Qty</th>
                <th style="width: 8%;">Unit</th>
                <th class="text-right" style="width: 18%;">Unit Price (IDR)</th>
                <th class="text-right" style="width: 18%;">Total Price (IDR)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupedItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    @if($hasAllocations)
                    <td style="font-size: 8.5pt; color: #495057;">{{ $item->allocation_names }}</td>
                    @endif
                    <td>{{ $item->material->code ?? '-' }}</td>
                    <td>
                        {{ $item->description }}
                        @if($item->component)
                            <br><small style="color: #666;">Component: {{ $item->component }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->qty, 2) }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $hasAllocations ? 8 : 7 }}" class="text-center">No items found in this Purchase Order.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-table">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">{{ number_format(floatval(str_replace(',', '', $po->subtotal)), 2) }}</td>
            </tr>
            @if(floatval(str_replace(',', '', $po->ppn_amount)) > 0)
            <tr>
                <td>PPN ({{ number_format(floatval(str_replace(',', '', $po->ppn_percent)), 0) }}%):</td>
                <td class="text-right">{{ number_format(floatval(str_replace(',', '', $po->ppn_amount)), 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td>Grand Total:</td>
                <td class="text-right">IDR {{ number_format(floatval(str_replace(',', '', $po->grand_total)), 2) }}</td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>

    @if($po->notes)
    <div class="section-title">NOTES</div>
    <div style="font-size: 9pt; color: #495057;">
        {{ $po->notes }}
    </div>
    @endif

    @php
        $manager = $po->approvals()->where('approval_level', 'manager')->where('status', 'approved')->first();
        $director = $po->approvals()->where('approval_level', 'director')->first();
        $hasDirector = $director !== null;
        $directorApproved = $director && $director->status === 'approved';
    @endphp

    <div class="signature-block">
        <table style="width: 100%; border: none; margin-top: 20px;">
            <tr style="border: none;">
                <!-- Prepared By Staff -->
                <td style="width: {{ $hasDirector ? '33%' : '50%' }}; text-align: center; border: none; padding: 0;">
                    <strong>Prepared By,</strong>
                    <div style="height: 60px; margin: 10px 0;"></div>
                    <div style="border-top: 1px solid #333; width: 140px; margin: 0 auto; padding-top: 5px; font-size: 9pt;">
                        Purchasing Staff
                    </div>
                </td>

                <!-- Manager Approval -->
                <td style="width: {{ $hasDirector ? '33%' : '50%' }}; text-align: center; border: none; padding: 0;">
                    <strong>Purchasing Manager,</strong>
                    <div style="height: 60px; margin: 10px 0;">
                        @if($manager && $manager->signature_path)
                            <img src="{{ $manager->signature_path }}" style="max-height: 50px; max-width: 140px;" />
                        @else
                            <div style="font-size: 8pt; color: #868e96; padding-top: 15px;">[Pending Signature]</div>
                        @endif
                    </div>
                    <div style="border-top: 1px solid #333; width: 140px; margin: 0 auto; padding-top: 5px; font-size: 9pt;">
                        {{ $manager->user->name ?? 'Authorized Manager' }}
                    </div>
                </td>

                <!-- Director Approval -->
                @if($hasDirector)
                    <td style="width: 33%; text-align: center; border: none; padding: 0;">
                        <strong>Finance Director,</strong>
                        <div style="height: 60px; margin: 10px 0;">
                            @if($directorApproved && $director->signature_path)
                                <img src="{{ $director->signature_path }}" style="max-height: 50px; max-width: 140px;" />
                            @else
                                <div style="font-size: 8pt; color: #868e96; padding-top: 15px;">[Pending Signature]</div>
                            @endif
                        </div>
                        <div style="border-top: 1px solid #333; width: 140px; margin: 0 auto; padding-top: 5px; font-size: 9pt;">
                            {{ $director->user->name ?? 'Finance Director' }}
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    </div>

    <div class="footer">
        This document is system-generated and valid without wet signature.
    </div>
</body>
</html>
