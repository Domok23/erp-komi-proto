<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order {{ $salesOrder->so_number }}</title>
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
        .signature-col { width: 45%; text-align: center; display: inline-block; }
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
        <div class="title">SALES ORDER</div>
    </div>

    <div class="metadata-container">
        <div class="left-col">
            <strong>CUSTOMER INFO:</strong><br>
            {{ $customer->name ?? '-' }}<br>
            {{ $customer->address ?? '-' }}<br>
            {{ $customer->city ?? '-' }}, {{ $customer->country ?? '-' }}<br>
            Phone: {{ $customer->phone ?? '-' }} | NPWP: {{ $customer->npwp ?? '-' }}
        </div>
        <div class="right-col">
            <strong>SO DETAILS:</strong><br>
            <table>
                <tr>
                    <td style="border: none; padding: 2px 0; width: 45%;">SO Number:</td>
                    <td style="border: none; padding: 2px 0;"><strong>{{ $salesOrder->so_number }}</strong></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Order Date:</td>
                    <td style="border: none; padding: 2px 0;">{{ $salesOrder->order_date?->format('d M Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Delivery Date:</td>
                    <td style="border: none; padding: 2px 0;">{{ $salesOrder->delivery_date?->format('d M Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Payment Terms:</td>
                    <td style="border: none; padding: 2px 0;">{{ strtoupper($salesOrder->payment_terms ?? '-') }}</td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>
    </div>

    <div class="section-title">ORDER ITEMS</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Description</th>
                <th class="text-right" style="width: 15%;">Qty</th>
                <th style="width: 10%;">Unit</th>
                <th class="text-right" style="width: 20%;">Unit Price ({{ $salesOrder->currency ?? 'IDR' }})</th>
                <th class="text-right" style="width: 20%;">Total Price ({{ $salesOrder->currency ?? 'IDR' }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesOrder->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center">{{ 1 }}</td>
                    <td>Sales Order Item</td>
                    <td class="text-right">{{ number_format($salesOrder->quantity, 2) }}</td>
                    <td>pcs</td>
                    <td class="text-right">{{ number_format($salesOrder->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($salesOrder->subtotal, 2) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-table">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">{{ number_format($salesOrder->subtotal, 2) }}</td>
            </tr>
            @if($salesOrder->ppn_amount > 0)
            <tr>
                <td>PPN ({{ number_format($salesOrder->ppn_percent, 0) }}%):</td>
                <td class="text-right">{{ number_format($salesOrder->ppn_amount, 2) }}</td>
            </tr>
            @endif
            @if($salesOrder->shipping_cost > 0)
            <tr>
                <td>Shipping:</td>
                <td class="text-right">{{ number_format($salesOrder->shipping_cost, 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td>Grand Total:</td>
                <td class="text-right">{{ $salesOrder->currency ?? 'IDR' }} {{ number_format($salesOrder->grand_total, 2) }}</td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>

    @if($salesOrder->notes)
    <div class="section-title">NOTES</div>
    <div style="font-size: 9pt; color: #495057;">
        {{ $salesOrder->notes }}
    </div>
    @endif

    <div class="signature-block">
        <div class="signature-col-left">
            <strong>Prepared By,</strong>
            <div class="signature-placeholder"></div>
            <div style="border-top: 1px solid #333; width: 180px; margin: 0 auto; padding-top: 5px;">
                Internal Staff
            </div>
        </div>
        <div class="signature-col-right">
            <strong>Customer Signature,</strong>
            <div>
                @if($salesOrder->customer_signature)
                    <img src="{{ $salesOrder->customer_signature }}" class="signature-img" />
                @else
                    <div class="signature-placeholder" style="border-bottom: 1px solid #ddd; width: 180px; margin: 10px auto 0;">[Pending Signature]</div>
                @endif
            </div>
            <div style="width: 180px; margin: 0 auto; padding-top: 5px;">
                {{ $customer->name ?? 'Customer representative' }}
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer">
        This document is system-generated and valid without wet signature.
    </div>
</body>
</html>
